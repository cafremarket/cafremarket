<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shop-level M-Pesa credentials table (package migration was never loaded).
     */
    public function up(): void
    {
        if (Schema::hasTable('config_mpesa')) {
            return;
        }

        Schema::create('config_mpesa', function (Blueprint $table) {
            $table->unsignedInteger('shop_id')->index();
            $table->string('consumer_key')->nullable();
            $table->text('consumer_secret')->nullable();
            $table->string('short_code')->nullable();
            $table->string('mpesa_passkey')->nullable();
            $table->string('lipa_na_mpesa')->nullable();
            $table->boolean('sandbox')->nullable()->default(true);
            $table->timestamps();

            $table->primary('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_mpesa');
    }
};
