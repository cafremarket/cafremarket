<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->dropUnique('delivery_boys_email_unique');
            $table->unique(['shop_id', 'email'], 'delivery_boys_shop_id_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->dropUnique('delivery_boys_shop_id_email_unique');
            $table->unique('email', 'delivery_boys_email_unique');
        });
    }
};
