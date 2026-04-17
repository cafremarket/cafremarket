<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliateCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('affiliate_commissions')) {
            Schema::create('affiliate_commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('affiliate_id');
                $table->foreign('affiliate_id')->references('id')->on('affiliates')->onDelete('cascade');

                $table->unsignedBigInteger('affiliate_link_id')->nullable();
                $table->foreign('affiliate_link_id')->references('id')->on('affiliate_links')->onDelete('set null');

                $table->unsignedBigInteger('inventory_id')->nullable();
                $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('set null');

                $table->unsignedBigInteger('order_id')->nullable();
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

                $table->boolean('paid')->default(false);
                $table->decimal('commission_rate', 8, 2);
                $table->decimal('total_commission', 20, 6);
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
        Schema::dropIfExists('affiliate_commissions');
    }
}
