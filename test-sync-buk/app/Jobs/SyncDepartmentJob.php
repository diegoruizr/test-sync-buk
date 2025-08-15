<?php

namespace App\Jobs;

use App\Constants\GlobalConstants;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;

class SyncDepartmentJob implements ShouldQueue
{
   use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

   public int $tries = GlobalConstants::JOB_TRIES;
   public $backoff = GlobalConstants::JOB_BACKOFF;

    public function __construct(public string $event, public array $data)
    {
        $this->queue = GlobalConstants::QUEUE_SYNC_DEPARTMENTS;
    }

    public function handle(): void
    {
         DB::transaction(function () {
            $id        = $this->data['id'];
            $createdAt = isset($this->data['created_at']) ? Carbon::parse($this->data['created_at']) : null;
            $updatedAt = isset($this->data['updated_at']) ? Carbon::parse($this->data['updated_at']) : null;
            $deletedAt = isset($this->data['deleted_at']) ? Carbon::parse($this->data['deleted_at']) : null;

            $incoming = $this->version($updatedAt, $deletedAt);

            $dept = Department::withTrashed()->lockForUpdate()->find($id);

            if (!$dept) {
                $dept = new Department();
                $dept->id = $id;
                if ($createdAt) {
                    $dept->setCreatedAt($createdAt);
                }
            } else {
                $current = $this->version($dept->updated_at, $dept->deleted_at);
                if ($incoming && $current && $incoming->lessThanOrEqualTo($current)) {
                    return; // idempotencia: ignorar duplicado/old
                }
            }

            $dept->timestamps = false;

               // Asignar solo si viene en el payload
            if (array_key_exists('name', $this->data))          $dept->name          = $this->data['name'];
            if (array_key_exists('cost_center_code', $this->data)) $dept->cost_center_code = $this->data['cost_center_code'];

            $dept->fill([
                'name'             => $this->data['name'] ?? $dept->name,
                'cost_center_code' => $this->data['cost_center_code'] ?? $dept->cost_center_code,
            ]);

            $dept->updated_at = $updatedAt ?? $dept->updated_at ?? now();

            if (!is_null($deletedAt) || $this->event === 'deleted') {
                $dept->deleted_at = $deletedAt ?? now();
            } elseif ($this->event === 'restored') {
                $dept->deleted_at = null;
            }

            $dept->saveQuietly();
        });
    }

    private function version(?Carbon $updatedAt, ?Carbon $deletedAt): ?Carbon
    {
        if ($updatedAt && $deletedAt) {
            return $updatedAt->greaterThan($deletedAt) ? $updatedAt : $deletedAt;
        }
        return $updatedAt ?: $deletedAt;
    }
}
