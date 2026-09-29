<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checkout payment held at the gateway before any order exists.
 * Orders are created from the carts only once the gateway confirms payment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_intents')) {
            return;
        }

        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('payment_method', 50)->index();
            $table->unsignedInteger('payment_method_id')->nullable();
            $table->string('channel', 10)->default('web'); // web, api
            $table->boolean('checkout_all')->default(false);
            $table->json('cart_ids');
            $table->decimal('amount', 20, 6);
            $table->decimal('fee', 20, 6)->default(0);
            $table->string('currency_code', 10)->nullable();
            // created, processing, pending, paid, completed, failed, cancelled, expired
            $table->string('status', 20)->default('created')->index();
            $table->string('msisdn', 30)->nullable();
            $table->string('gateway_ref', 100)->nullable()->index();
            $table->string('emola_trans_id', 30)->nullable()->index();
            $table->string('emola_ref_no', 30)->nullable()->index();
            $table->string('emola_request_id', 64)->nullable();
            $table->string('gateway_code', 30)->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->json('order_ids')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
