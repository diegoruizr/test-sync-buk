<?php

namespace App\Observers;

use App\Http\Resources\DepartmentResource;
use App\Jobs\SyncDepartmentJob;
use App\Models\Department;
use App\Services\Outbox\OutboxRecorder;

class DepartmentObserver
{

    private function payload(Department $m): array
    {
        return (new DepartmentResource($m->fresh()))->resolve();
    }

    /**
     * Handle the Department "created" event.
     */
    public function created(Department $department): void
    {
        OutboxRecorder::record('department', $department->id, 'created',  $this->payload($department));
    }

    /**
     * Handle the Department "updated" event.
     */
    public function updated(Department $department): void
    {
        OutboxRecorder::record('department', $department->id, 'updated',  $this->payload($department));
    }

    /**
     * Handle the Department "deleted" event.
     */
    public function deleted(Department $department): void
    {
        OutboxRecorder::record('department', $department->id, 'deleted',  $this->payload($department));
    }

    /**
     * Handle the Department "restored" event.
     */
    public function restored(Department $department): void
    {
        // SyncDepartmentJob::dispatch('restored', $this->payload($department))->onQueue('sync_departments')->afterCommit();
        OutboxRecorder::record('department', $department->id, 'restored',  $this->payload($department));
    }
}
