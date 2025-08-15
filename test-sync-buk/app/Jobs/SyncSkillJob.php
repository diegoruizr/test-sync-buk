<?php

namespace App\Jobs;

use App\Constants\GlobalConstants;
use App\Models\Skill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncSkillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = GlobalConstants::JOB_TRIES;
    public $backoff = GlobalConstants::JOB_BACKOFF;

    public function __construct(
        public string $event,
        public array $data
    ) {
        $this->onQueue(GlobalConstants::QUEUE_SYNC_SKILLS);
        $this->afterCommit();
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $id        = $this->data['id'];
            $createdAt = !empty($this->data['created_at']) ? Carbon::parse($this->data['created_at']) : null;
            $updatedAt = !empty($this->data['updated_at']) ? Carbon::parse($this->data['updated_at']) : null;
            $deletedAt = array_key_exists('deleted_at', $this->data) && $this->data['deleted_at']
                ? Carbon::parse($this->data['deleted_at'])
                : null;

            $incoming = $this->version($updatedAt, $deletedAt);

            $m = Skill::withTrashed()->lockForUpdate()->find($id);

            if (!$m) {
                $m     = new Skill();
                $m->id = $id;
                if ($createdAt) $m->setCreatedAt($createdAt);
            } else {
                $current = $this->version($m->updated_at, $m->deleted_at);
                if ($incoming && $current && $incoming->lessThanOrEqualTo($current)) {
                    return; // idempotencia
                }
            }

            $m->timestamps = false;

            if (array_key_exists('name', $this->data))            $m->name = $this->data['name'];
            if (array_key_exists('level_required', $this->data))  $m->level_required = (int) $this->data['level_required'];

            $m->updated_at = $updatedAt ?? $m->updated_at ?? now();

            if (!is_null($deletedAt) || $this->event === 'deleted') {
                $m->deleted_at = $deletedAt ?? now();
            } elseif ($this->event === 'restored') {
                $m->deleted_at = null;
            } elseif (array_key_exists('deleted_at', $this->data)) {
                $m->deleted_at = $deletedAt;
            }

            $m->saveQuietly();
        });
    }

    private function version(?Carbon $u, ?Carbon $d): ?Carbon
    {
        if ($u && $d) return $u->greaterThan($d) ? $u : $d;
        return $u ?: $d;
    }
}
