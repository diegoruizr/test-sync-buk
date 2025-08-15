<?php

namespace App\Services\Outbox;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OutboxRecorder
{
    public static function record(string $aggregateType, string $aggregateId, string $event, array $payload): void
    {
        $map = config("outbox.map.{$aggregateType}");
        if (!$map) {
            throw new \InvalidArgumentException("Outbox: aggregateType '{$aggregateType}' no mapeado.");
        }

        DB::table('outbox')->insert([
            'id'             => (string) Str::uuid(),
            'aggregate_type' => $aggregateType,
            'aggregate_id'   => $aggregateId,
            'event'          => $event,
            'job_class'      => $map['job'],
            'queue'          => $map['queue'],
            'payload'        => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'occurred_at'    => now()->toISOString(),
            'created_at'     => now()->toISOString(),
            'updated_at'     => now()->toISOString(),
        ]);
    }
}
