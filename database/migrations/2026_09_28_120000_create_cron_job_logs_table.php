<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per scheduled task run (admin Cron jobs page).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cron_job_logs')) {
            return;
        }

        Schema::create('cron_job_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task', 191)->index();
            $table->text('command')->nullable();
            $table->string('expression', 64)->nullable();
            $table->string('status', 20)->index(); // running, success, failed, skipped
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->integer('exit_code')->nullable();
            $table->mediumText('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_job_logs');
    }
};
