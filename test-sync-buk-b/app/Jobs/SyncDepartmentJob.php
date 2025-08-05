<?php

namespace App\Jobs;

use App\Models\Department;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log as FacadesLog;

class SyncDepartmentJob implements ShouldQueue
{
   use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        //Guardar o actualizar el departamento en Sistema B
        try {
            Department::updateOrCreate(
                ['id' => $this->data['id']],
                [
                    'name' => $this->data['name'],
                    'cost_center_code' => $this->data['cost_center_code'],
                ]
            );
        } catch (\Throwable $th) {
            dump('Error al sincronizar el departamento: ' . $th->getMessage());
        } 
    }
}
