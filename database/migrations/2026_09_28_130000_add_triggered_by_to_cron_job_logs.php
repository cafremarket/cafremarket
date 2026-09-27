<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who started a cron run: null = the scheduler, otherwise "Manual · <admin name>".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cron_job_logs') && ! Schema::hasColumn('cron_job_logs', 'triggered_by')) {
            Schema::table('cron_job_logs', function (Blueprint $table) {
                $table->string('triggered_by', 191)->nullable()->after('expression');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cron_job_logs', 'triggered_by')) {
            Schema::table('cron_job_logs', function (Blueprint $table) {
                $table->dropColumn('triggered_by');
            });
        }
    }
};
