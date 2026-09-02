<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'preferred_address_id')) {
                $table->unsignedBigInteger('preferred_address_id')->nullable()->after('preferred_address_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'preferred_address_id')) {
                $table->dropColumn('preferred_address_id');
            }
        });
    }
};
