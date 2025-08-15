<?php

namespace App\Jobs;

use App\Constants\GlobalConstants;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncEmployeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = GlobalConstants::JOB_TRIES;
    public $backoff = GlobalConstants::JOB_BACKOFF;

    public function __construct(
        public string $event,
        public array $data
    ) {
        // Configura por métodos
        $this->onQueue(GlobalConstants::QUEUE_SYNC_EMPLOYEES);
        $this->afterCommit();
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $id        = $this->data['id'];
            $createdAt = isset($this->data['created_at']) ? Carbon::parse($this->data['created_at']) : null;
            $updatedAt = isset($this->data['updated_at']) ? Carbon::parse($this->data['updated_at']) : null;
            $deletedAt = array_key_exists('deleted_at', $this->data) && $this->data['deleted_at'] ? Carbon::parse($this->data['deleted_at']) : null;

            $incoming = $this->version($updatedAt, $deletedAt);

            $emp = Employee::withTrashed()->lockForUpdate()->find($id);

            if (!$emp) {
                $emp     = new Employee();
                $emp->id = $id;
                if ($createdAt) {
                    $emp->setCreatedAt($createdAt);
                }
            } else {
                $current = $this->version($emp->updated_at, $emp->deleted_at);
                if ($incoming && $current && $incoming->lessThanOrEqualTo($current)) {
                    return; // idempotencia: duplicado o fuera de orden
                }
            }

            // No sobrescribir timestamps automáticamente
            $emp->timestamps = false;

            // Asignar solo si viene en el payload
            if (array_key_exists('name', $this->data))          $emp->name          = $this->data['name'];
            if (array_key_exists('email', $this->data))         $emp->email         = $this->data['email'];
            if (array_key_exists('position', $this->data))      $emp->position      = $this->data['position'];
            if (array_key_exists('hire_date', $this->data))     $emp->hire_date     = $this->data['hire_date'];
            if (array_key_exists('department_id', $this->data)) $emp->department_id = $this->data['department_id'];
            if (array_key_exists('is_active', $this->data))     $emp->is_active     = (bool) $this->data['is_active'];

            // Timestamps desde el origen
            $emp->updated_at = $updatedAt ?? $emp->updated_at ?? now();

            // Borrado/restauración
            if (!is_null($deletedAt) || $this->event === 'deleted') {
                $emp->deleted_at = $deletedAt ?? now();
            } elseif ($this->event === 'restored') {
                $emp->deleted_at = null;
            } elseif (array_key_exists('deleted_at', $this->data)) {
                // permitir null explícito
                $emp->deleted_at = $deletedAt;
            }

            $emp->saveQuietly();
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
