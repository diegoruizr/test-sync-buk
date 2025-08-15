<?php

namespace App\Jobs;

use App\Constants\GlobalConstants;
use App\Models\EmployeeSkill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncEmployeeSkillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = GlobalConstants::JOB_TRIES;
    public $backoff = GlobalConstants::JOB_BACKOFF;

    public function __construct(
        public string $event,
        public array $data
    ) {
        $this->onQueue(GlobalConstants::QUEUE_SYNC_EMPLOYEE_SKILLS);
        $this->afterCommit();
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $level     = $this->data['level'] ?? null;
            $empId     = $this->data['employee_id'];
            $skillId   = $this->data['skill_id'];
            
            $updatedAt = isset($this->data['updated_at']) ? Carbon::parse($this->data['updated_at']) : null;
            $deletedAt = array_key_exists('deleted_at', $this->data) && $this->data['deleted_at']
                ? Carbon::parse($this->data['deleted_at']) : null;

            $incoming = $this->version($updatedAt, $deletedAt);

            $pivot = EmployeeSkill::withTrashed()
                ->where('employee_id', $empId)
                ->where('skill_id', $skillId)
                ->lockForUpdate()
                ->first();

            if (!$pivot) {
                $pivot = new EmployeeSkill([
                    'employee_id' => $empId,
                    'skill_id'    => $skillId,
                ]);
            } else {
                $current = $this->version($pivot->updated_at, $pivot->deleted_at);
                if ($incoming && $current && $incoming->lessThan($current)) {
                    return; // idempotencia
                }
            }

            $pivot->timestamps = false;

            if (!is_null($level)) $pivot->level = (int) $level;

            if ($updatedAt) {
                $pivot->updated_at = $updatedAt;
            }

            if ($this->event === 'deleted') {
                if ($deletedAt) {
                    $pivot->deleted_at = $deletedAt;
                } elseif ($updatedAt) {
                    $pivot->deleted_at = $updatedAt;
                }
            } elseif ($this->event === 'restored') {
                $pivot->deleted_at = null;
            } elseif (array_key_exists('deleted_at', $this->data)) {
                $pivot->deleted_at = $deletedAt;
            }

            $pivot->saveQuietly();
        });
    }

    private function version(?Carbon $u, ?Carbon $d): ?Carbon
    {
        if ($u && $d) return $u->greaterThan($d) ? $u : $d;
        return $u ?: $d;
    }
}
