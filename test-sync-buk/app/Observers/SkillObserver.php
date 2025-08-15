<?php

namespace App\Observers;

use App\Http\Resources\SkillResource;
use App\Jobs\SyncSkillJob;
use App\Models\Skill;
use App\Services\Outbox\OutboxRecorder;

class SkillObserver
{
    private function payload(Skill $m): array
    {
        return (new SkillResource($m->fresh()))->resolve();
    }

    /**
     * Handle the Skill "created" event.
     */
    public function created(Skill $skill): void
    {
        OutboxRecorder::record('skill', $skill->id, 'created',  $this->payload($skill));
    }

    /**
     * Handle the Skill "updated" event.
     */
    public function updated(Skill $skill): void
    {
        OutboxRecorder::record('skill', $skill->id, 'updated',  $this->payload($skill));
    }

    /**
     * Handle the Skill "deleted" event.
     */
    public function deleted(Skill $skill): void
    {
        OutboxRecorder::record('skill', $skill->id, 'deleted',  $this->payload($skill));
    }

    /**
     * Handle the Skill "restored" event.
     */
    public function restored(Skill $skill): void
    {
        // SyncSkillJob::dispatch('restored', $this->payload($skill))->onQueue('sync_skills')->afterCommit();
        OutboxRecorder::record('skill', $skill->id, 'restored',  $this->payload($skill));
    }
}
