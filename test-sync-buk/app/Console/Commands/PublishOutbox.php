<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use App\Constants\GlobalConstants;

class PublishOutbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:publish {--batch=200}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publica eventos pendientes desde la tabla outbox hacia RabbitMQ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batch = (int) $this->option('batch');

        // Trae mensajes no publicados, en orden de ocurrencia
        $rows = DB::table(GlobalConstants::OUTBOX)
            ->whereNull('published_at')
            ->orderBy('occurred_at')
            ->limit($batch)
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Outbox vacía.');
            return self::SUCCESS;
        }

        $published = 0;

        foreach ($rows as $row) {
            try {
                $payload = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
                /** @var class-string<\Illuminate\Contracts\Queue\ShouldQueue> $jobClass */
                $jobClass = $row->job_class;

                // Instancia el Job con (event, payload)
                $job = new $jobClass($row->event, $payload);

                // Publica a la conexión/cola configuradas
                dispatch($job)
                    ->onConnection(config('outbox.connection'))
                    ->onQueue($row->queue);

                // Marca como publicado
                DB::table(GlobalConstants::OUTBOX)->where('id', $row->id)->update([
                    'published_at' => now()->toISOString(),
                    'attempts'     => DB::raw('attempts + 1'),
                    'last_error'   => null,
                    'updated_at'   => now()->toISOString(),
                ]);

                $published++;
            } catch (\Throwable $e) {
                // Incrementa intentos y guarda error; se reintenta en la próxima corrida
                DB::table('outbox')->where('id', $row->id)->update([
                    'attempts'   => DB::raw('attempts + 1'),
                    'last_error' => mb_substr($e->getMessage(), 0, 2000),
                    'updated_at' => now()->toISOString(),
                ]);
                $this->error("Error publicando {$row->id}: ".$e->getMessage());
            }
        }

        $this->info("Publicados: {$published} eventos.");
        return self::SUCCESS;
    }
}
