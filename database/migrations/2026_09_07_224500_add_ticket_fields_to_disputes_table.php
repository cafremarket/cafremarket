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
            if (! Schema::hasColumn('disputes', 'raised_by')) {
                $table->string('raised_by', 20)->default('customer')->after('customer_id');
            }
            if (! Schema::hasColumn('disputes', 'ticket_number')) {
                $table->string('ticket_number', 40)->nullable()->after('id');
                $table->index('ticket_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('disputes')) {
            return;
        }

        Schema::table('disputes', function (Blueprint $table) {
            if (Schema::hasColumn('disputes', 'ticket_number')) {
                $table->dropIndex(['ticket_number']);
                $table->dropColumn('ticket_number');
            }
            if (Schema::hasColumn('disputes', 'raised_by')) {
                $table->dropColumn('raised_by');
            }
        });
    }
};
