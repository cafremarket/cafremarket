<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('disputes')) {
            return;
        }

        Schema::table('disputes', function (Blueprint $table) {
            if (! Schema::hasColumn('disputes', 'resolved_by')) {
                $table->string('resolved_by', 20)->nullable()->after('status');
            }
            if (! Schema::hasColumn('disputes', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            }
            if (! Schema::hasColumn('disputes', 'close_requested_by')) {
                $table->string('close_requested_by', 20)->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('disputes', 'close_requested_at')) {
                $table->timestamp('close_requested_at')->nullable()->after('close_requested_by');
            }
            if (! Schema::hasColumn('disputes', 'closed_by')) {
                $table->unsignedInteger('closed_by')->nullable()->after('close_requested_at');
            }
            if (! Schema::hasColumn('disputes', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('closed_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('disputes')) {
            return;
        }

        Schema::table('disputes', function (Blueprint $table) {
            foreach (['resolved_by', 'resolved_at', 'close_requested_by', 'close_requested_at', 'closed_by', 'closed_at'] as $column) {
                if (Schema::hasColumn('disputes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
