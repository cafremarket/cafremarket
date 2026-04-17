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
            if (! Schema::hasColumn('orders', 'wire_transfer_proof_path')) {
                $table->string('wire_transfer_proof_path')->nullable()->after('payment_instruction');
            }

            if (! Schema::hasColumn('orders', 'wire_transfer_proof_name')) {
                $table->string('wire_transfer_proof_name')->nullable()->after('wire_transfer_proof_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'wire_transfer_proof_name')) {
                $table->dropColumn('wire_transfer_proof_name');
            }

            if (Schema::hasColumn('orders', 'wire_transfer_proof_path')) {
                $table->dropColumn('wire_transfer_proof_path');
            }
        });
    }
};
