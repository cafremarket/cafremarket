<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->text('verification_rejection_reason')->nullable()->after('pending_verification');
            $table->timestamp('verification_rejected_at')->nullable()->after('verification_rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn(['verification_rejection_reason', 'verification_rejected_at']);
        });
    }
};
