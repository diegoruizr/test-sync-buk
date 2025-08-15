<?php

namespace App\Observers;

use App\Http\Resources\EmployeeSkillResource;
use App\Jobs\SyncEmployeeSkillJob;
use App\Models\EmployeeSkill;
use App\Services\Outbox\OutboxRecorder;
use Ramsey\Uuid\Uuid;

class EmployeeSkillObserver
{

    private function aggregateId(EmployeeSkill $p): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, $p->employee_id.'|'.$p->skill_id)->toString();
    }

    private function payload(EmployeeSkill $p): array
    {
        return (new EmployeeSkillResource($p))->resolve();
    }

    /**
     * Handle the EmployeeSkill "created" event.
     */
    public function created(EmployeeSkill $employeeSkill): void
    {
        OutboxRecorder::record('employee_skill', $this->aggregateId($employeeSkill), 'created',  $this->payload($employeeSkill));
    }

    /**
     * Handle the EmployeeSkill "updated" event.
     */
    public function updated(EmployeeSkill $employeeSkill): void
    {
        OutboxRecorder::record('employee_skill', $this->aggregateId($employeeSkill), 'updated',  $this->payload($employeeSkill));
    }

    /**
     * Handle the EmployeeSkill "deleted" event.
     */
    public function deleted(EmployeeSkill $employeeSkill): void
    {
        OutboxRecorder::record('employee_skill', $this->aggregateId($employeeSkill), 'deleted',  $this->payload($employeeSkill));
    }

    /**
     * Handle the EmployeeSkill "restored" event.
     */
    public function restored(EmployeeSkill $employeeSkill): void
    {
        // SyncEmployeeSkillJob::dispatch('restored', $this->payload($employeeSkill))->onQueue('sync_employee_skills')->afterCommit();
        OutboxRecorder::record('employee_skill', $this->aggregateId($employeeSkill), 'restored',  $this->payload($employeeSkill));
    }
}
