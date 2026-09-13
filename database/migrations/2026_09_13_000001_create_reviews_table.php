<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 20); // 'product' | 'store'
            $table->unsignedBigInteger('reviewable_id');
            $table->string('reviewable_type');
            $table->unsignedInteger('shop_id');
            $table->unsignedBigInteger('customer_id');
            // Informational only: the order that first proved the customer purchased
            // this product/shop. Not part of the review's identity - a review belongs
            // to (customer, reviewable), not to an order, so a customer gets exactly
            // one review per product/shop no matter how many times they've bought it.
            $table->unsignedBigInteger('order_id')->nullable();
            $table->tinyInteger('rating');
            $table->longText('comment')->nullable();
            $table->boolean('approved')->default(1);
            $table->boolean('spam')->default(0);
            $table->longText('reply')->nullable();
            $table->unsignedBigInteger('replied_by')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reviewable_type', 'reviewable_id']);
            $table->index(['shop_id', 'type']);
            // One review per customer per product/store - the core "product based, not
            // order based" rule. Writing again edits the existing review in place.
            $table->unique(['customer_id', 'reviewable_type', 'reviewable_id'], 'reviews_customer_reviewable_unique');

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('replied_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
