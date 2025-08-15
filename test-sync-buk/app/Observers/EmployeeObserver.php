<?php

namespace App\Observers;

use App\Http\Resources\EmployeeResource;
use App\Jobs\SyncEmployeeJob;
use App\Models\Employee;
use App\Services\Outbox\OutboxRecorder;

class EmployeeObserver
{
     private function payload(Employee $m): array
    {
        return (new EmployeeResource($m->fresh()))->resolve();
    }
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        OutboxRecorder::record('employee', $employee->id, 'created',  $this->payload($employee));
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        OutboxRecorder::record('employee', $employee->id, 'updated',  $this->payload($employee));
    }

    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        OutboxRecorder::record('employee', $employee->id, 'deleted',  $this->payload($employee));
    }

    /**
     * Handle the Employee "restored" event.
     */
    public function restored(Employee $employee): void
    {
        // SyncEmployeeJob::dispatch('restored', $this->payload($employee))->onQueue('sync_employees')->afterCommit();
        OutboxRecorder::record('employee', $employee->id, 'restored',  $this->payload($employee));
    }
}
