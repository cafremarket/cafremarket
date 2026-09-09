<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production activated Cafrepay/wallet without ever creating its tables
 * (WalletServiceProvider did not load package migrations). Create them here
 * so a normal `php artisan migrate` recovers missing wallets/transactions/transfers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('holder');
                $table->string('name');
                $table->string('slug')->index();
                $table->string('description')->nullable();
                $table->json('meta')->nullable();
                $table->decimal('balance', 64, 6)->default(0);
                $table->boolean('blocked')->nullable()->default(null);
                $table->timestamps();

                $table->unique(['holder_type', 'holder_id', 'slug']);
            });
        }

        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('payable');
                $table->unsignedBigInteger('wallet_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->enum('type', ['deposit', 'withdraw'])->index();
                $table->decimal('amount', 64, 6);
                $table->decimal('balance', 64, 6);
                $table->boolean('confirmed');
                $table->boolean('approved')->nullable();
                $table->json('meta')->nullable();
                $table->uuid('uuid')->unique();
                $table->timestamps();

                $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
                if (Schema::hasTable('orders')) {
                    $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
                }

                $table->index(['payable_type', 'payable_id', 'type'], 'payable_type_ind');
                $table->index(['payable_type', 'payable_id', 'confirmed'], 'payable_confirmed_ind');
                $table->index(['payable_type', 'payable_id', 'type', 'confirmed'], 'payable_type_confirmed_ind');
            });
        } elseif (! Schema::hasColumn('transactions', 'order_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->after('wallet_id')->nullable();
                if (Schema::hasTable('orders')) {
                    $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
                }
            });
        }

        if (! Schema::hasTable('transfers')) {
            Schema::create('transfers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('from');
                $table->morphs('to');
                // string (not enum) — matches later wallet package alter migration
                $table->string('status')->default('transfer');
                $table->string('status_last')->nullable();
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
        // Do not drop live wallet data on rollback.
    }
};
