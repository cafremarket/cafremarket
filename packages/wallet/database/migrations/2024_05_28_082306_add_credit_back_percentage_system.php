<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCreditBackPercentageSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('wallet_credit_rewards')) {
            Schema::create('wallet_credit_rewards', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('shop_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->text('details')->nullable();
                $table->decimal('amount', 20, 6)->default(0);
                $table->decimal('fee', 20, 6)->default(0);
                $table->boolean('released')->nullable()->default(null);
                $table->timestamps();

                $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            });
        }

        Schema::table('configs', function (Blueprint $table) {
            if (! Schema::hasColumn('configs', 'credit_back_percentage')) {
                $table->decimal('credit_back_percentage', 8, 2)->default(0)->after('order_handling_cost');
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('inventories', 'credit_back_percentage')) {
                $table->decimal('credit_back_percentage', 8, 2)->nullable()->default(null)->after('free_shipping');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumns('order_items', ['credit_back_amount'])) {
                $table->decimal('credit_back_amount', 8, 2)->after('unit_price')->default(0);
            }
        });

        Schema::table('shops', function (Blueprint $table) {
            if (! Schema::hasColumn('shops', 'total_reward_given')) {
                $table->decimal('total_reward_given', 64, 6)->default(0)->after('total_item_sold');
            }
        });

        DB::table(get_option_table_name())->insert([
            'option_name' => 'wallet_credit_reward_system',
            'option_value' => true,
            'autoload' => true,
            'created_at' => Carbon::Now(),
            'updated_at' => Carbon::Now(),
        ], [
            'option_name' => 'wallet_release_credit_rewards_in_days',
            'option_value' => config('wallet.default.credit_back_reward_release_in', 3),
            'autoload' => true,
            'created_at' => Carbon::Now(),
            'updated_at' => Carbon::Now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'total_reward_given')) {
                $table->dropColumn('total_reward_given');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumns('order_items', ['credit_back_amount'])) {
                $table->dropColumn('credit_back_amount');
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'credit_back_percentage')) {
                $table->dropColumn('credit_back_percentage');
            }
        });

        Schema::table('configs', function (Blueprint $table) {
            if (Schema::hasColumn('configs', 'credit_back_percentage')) {
                $table->dropColumn('credit_back_percentage');
            }
        });

        Schema::dropIfExists('wallet_credit_rewards');

        DB::table(get_option_table_name())->where('option_name', 'wallet_credit_reward_system')->delete();
    }
}
