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
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'phone')) {
                $table->string('phone')->after('email')->nullable();
            }
            if (! Schema::hasColumn('customers', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->after('remember_token')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->after('email')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->after('remember_token')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
