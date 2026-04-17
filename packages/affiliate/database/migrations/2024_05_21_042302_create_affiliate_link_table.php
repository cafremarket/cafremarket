<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliateLinkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('affiliate_links')) {
            Schema::create('affiliate_links', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();

                $table->unsignedBigInteger('affiliate_id')->nullable();
                $table->foreign('affiliate_id')->references('id')->on('affiliates')->onDelete('set null');

                $table->unsignedBigInteger('inventory_id')->nullable();
                $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('set null');

                $table->integer('visitor_count')->default(0);
                $table->integer('order_count')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('affiliate_links');
    }
}
