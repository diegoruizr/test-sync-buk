<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('outbox', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('aggregate_type');
            $t->uuid('aggregate_id');
            $t->string('event');
            $t->string('job_class');
            $t->string('queue');
            $t->jsonb('payload');
            $t->timestampTz('occurred_at');
            $t->timestampTz('published_at')->nullable();
            $t->unsignedInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestampsTz();

            $t->index(['published_at', 'aggregate_type']);
            $t->index(['aggregate_type', 'aggregate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbox');
    }
};
