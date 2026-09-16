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
        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('description');
            }
            if (! Schema::hasColumn('refunds', 'failure_reason')) {
                $table->string('failure_reason', 500)->nullable()->after('admin_note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (Schema::hasColumn('refunds', 'failure_reason')) {
                $table->dropColumn('failure_reason');
            }
            if (Schema::hasColumn('refunds', 'admin_note')) {
                $table->dropColumn('admin_note');
            }
        });
    }
};
