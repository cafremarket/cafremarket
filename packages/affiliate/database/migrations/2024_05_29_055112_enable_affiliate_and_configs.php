<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnableAffiliateAndConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // System Table
        Schema::table('systems', function (Blueprint $table) {
            if (!Schema::hasColumn('systems', 'affiliate_commission_release_in_days')) {
                $table->integer('affiliate_commission_release_in_days')->nullable()->default(null)->after('can_use_own_catalog_only');
            }

            if (!Schema::hasColumn('systems', 'publicly_show_affiliate_commission')) {
                $table->boolean('publicly_show_affiliate_commission')->default(true)->after('affiliate_commission_release_in_days');
            }
        });

        // Inventory Table
        Schema::table('inventories', function (Blueprint $table) {
            if (!Schema::hasColumn('inventories', 'affiliate_commission_percentage')) {
                $table->decimal('affiliate_commission_percentage', 8, 2)->nullable()->after('available_from');
            }
        });

        // Carts Table
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'affiliate_id')) {
                $table->unsignedBigInteger('affiliate_id')->nullable()->after('customer_id');
                $table->foreign('affiliate_id')->references('id')->on('affiliates')->onDelete('set null');
            }

            if (!Schema::hasColumn('carts', 'affiliate_commission_amount')) {
                $table->decimal('affiliate_commission_amount', 8, 2);
            }
        });

        // Orders Table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'affiliate_id')) {
                $table->unsignedBigInteger('affiliate_id')->nullable()->after('customer_id');
                $table->foreign('affiliate_id')->references('id')->on('affiliates')->onDelete('set null');
            }

            if (!Schema::hasColumn('orders', 'affiliate_commission_amount')) {
                $table->decimal('affiliate_commission_amount', 8, 2)->after('grand_total')->default(0);
            }

            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'affiliate_commission_amount')) {
                    $table->decimal('affiliate_commission_amount', 8, 2)->after('unit_price')->default(0);
                }
            });
        });

        // configs table
        Schema::table('configs', function (Blueprint $table) {
            if (!Schema::hasColumn('configs', 'default_affiliate_commission_percentage')) {
                $table->decimal('default_affiliate_commission_percentage', 8, 2)->nullable()->after('default_packaging_ids');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'affiliate_commission_amount')) {
                $table->dropColumn('affiliate_commission_amount');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'affiliate_commission_amount')) {
                $table->dropColumn('affiliate_commission_amount');
            }
        });

        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'affiliate_commission_amount')) {
                $table->dropColumn('affiliate_commission_amount');
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'affiliate_commission_percentage')) {
                $table->dropColumn('affiliate_commission_percentage');
            }
        });

        Schema::table('systems', function (Blueprint $table) {
            if (Schema::hasColumn('systems', 'publicly_show_affiliate_commission')) {
                $table->dropColumn('publicly_show_affiliate_commission');
            }

            if (Schema::hasColumn('systems', 'affiliate_commission_release_in_days')) {
                $table->dropColumn('affiliate_commission_release_in_days');
            }
        });

        Schema::table('configs', function (Blueprint $table) {
            if (Schema::hasColumn('configs', 'default_affiliate_commission_percentage')) {
                $table->dropColumn('default_affiliate_commission_percentage');
            }
        });
    }
}
