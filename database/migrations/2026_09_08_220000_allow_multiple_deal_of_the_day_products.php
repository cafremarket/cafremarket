<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowMultipleDealOfTheDayProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deal_of_the_day', function (Blueprint $table) {
            $table->dropUnique(['deal_date']);
            $table->unique(['deal_date', 'inventory_id'], 'deal_of_the_day_date_inventory_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deal_of_the_day', function (Blueprint $table) {
            $table->dropUnique('deal_of_the_day_date_inventory_unique');
            $table->unique('deal_date');
        });
    }
}
