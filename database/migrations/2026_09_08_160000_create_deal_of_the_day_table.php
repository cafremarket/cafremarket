<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDealOfTheDayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deal_of_the_day', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('deal_date');
            $table->unsignedBigInteger('inventory_id');
            $table->timestamps();

            $table->unique(['deal_date', 'inventory_id'], 'deal_of_the_day_date_inventory_unique');
            $table->foreign('inventory_id')
                ->references('id')
                ->on('inventories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deal_of_the_day');
    }
}
