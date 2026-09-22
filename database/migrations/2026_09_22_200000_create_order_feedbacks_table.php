<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_feedbacks', function (Blueprint $table) {
            $table->bigIncrements('id');
            // One order, one feedback: the customer's overall experience with this order.
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedInteger('shop_id');
            $table->unsignedBigInteger('customer_id');
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('shop_id');
            $table->index('customer_id');

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_feedbacks');
    }
};
