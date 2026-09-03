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
        Schema::table('shops', function (Blueprint $table) {
            if (! Schema::hasColumn('shops', 'seller_type')) {
                $table->string('seller_type', 20)->default('company')->after('legal_name');
            }

            if (! Schema::hasColumn('shops', 'nuit')) {
                $table->string('nuit', 32)->nullable()->after('seller_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'nuit')) {
                $table->dropColumn('nuit');
            }

            if (Schema::hasColumn('shops', 'seller_type')) {
                $table->dropColumn('seller_type');
            }
        });
    }
};
