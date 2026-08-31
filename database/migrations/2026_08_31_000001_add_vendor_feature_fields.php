<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            if (! Schema::hasColumn('configs', 'verification_meta')) {
                $table->json('verification_meta')->nullable()->after('verification_rejected_at');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'nuit')) {
                $table->string('nuit', 64)->nullable()->after('contact_person');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            if (Schema::hasColumn('configs', 'verification_meta')) {
                $table->dropColumn('verification_meta');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'nuit')) {
                $table->dropColumn('nuit');
            }
        });
    }
};
