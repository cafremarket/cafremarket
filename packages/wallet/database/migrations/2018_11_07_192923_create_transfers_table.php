<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Incevio\Package\Wallet\Enums\TransferStatuses;

class CreateTransfersTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transfers')) {
            Schema::create('transfers', function (Blueprint $table) {
                $enums = [
                    TransferStatuses::STATUS_EXCHANGE,
                    TransferStatuses::STATUS_TRANSFER,
                    TransferStatuses::STATUS_PAID,
                    TransferStatuses::STATUS_REFUND,
                    TransferStatuses::STATUS_GIFT,
                ];

                $table->bigIncrements('id');
                $table->morphs('from');
                $table->morphs('to');
                $table->enum('status', $enums)->default(TransferStatuses::STATUS_TRANSFER);
                $table->enum('status_last', $enums)->nullable();
                $table->unsignedBigInteger('deposit_id');
                $table->unsignedBigInteger('withdraw_id');
                $table->decimal('discount', 64, 6)->default(0);
                $table->decimal('fee', 64, 6)->default(0);
                $table->uuid('uuid')->unique();
                $table->timestamps();

                $table->foreign('deposit_id')->references('id')->on('transactions')->onDelete('cascade');
                $table->foreign('withdraw_id')->references('id')->on('transactions')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
}
