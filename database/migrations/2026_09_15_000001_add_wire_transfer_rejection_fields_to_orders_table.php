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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'wire_transfer_rejected_at')) {
                $table->timestamp('wire_transfer_rejected_at')->nullable()->after('wire_transfer_proof_name');
            }

            if (! Schema::hasColumn('orders', 'wire_transfer_rejection_reason')) {
                $table->text('wire_transfer_rejection_reason')->nullable()->after('wire_transfer_rejected_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'wire_transfer_rejection_reason')) {
                $table->dropColumn('wire_transfer_rejection_reason');
            }

            if (Schema::hasColumn('orders', 'wire_transfer_rejected_at')) {
                $table->dropColumn('wire_transfer_rejected_at');
            }
        });
    }
};
