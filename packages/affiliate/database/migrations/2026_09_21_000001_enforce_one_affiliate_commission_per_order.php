<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('affiliate_commissions')) {
            $keepIds = DB::table('affiliate_commissions')
                ->select(DB::raw('MAX(id) as keep_id'))
                ->whereNotNull('order_id')
                ->groupBy('order_id')
                ->pluck('keep_id');

            if ($keepIds->isNotEmpty()) {
                DB::table('affiliate_commissions')
                    ->whereNotNull('order_id')
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            }

            $indexes = collect(Schema::getIndexes('affiliate_commissions'))->pluck('name');
            if (! $indexes->contains('affiliate_commissions_order_id_unique')) {
                Schema::table('affiliate_commissions', function (Blueprint $table) {
                    $table->unique('order_id', 'affiliate_commissions_order_id_unique');
                });
            }
        }

        if (Schema::hasTable('affiliate_links')) {
            $keepLinkIds = DB::table('affiliate_links')
                ->select(DB::raw('MAX(id) as keep_id'))
                ->whereNotNull('affiliate_id')
                ->whereNotNull('inventory_id')
                ->groupBy('affiliate_id', 'inventory_id')
                ->pluck('keep_id');

            if ($keepLinkIds->isNotEmpty()) {
                DB::table('affiliate_links')
                    ->whereNotNull('affiliate_id')
                    ->whereNotNull('inventory_id')
                    ->whereNotIn('id', $keepLinkIds)
                    ->delete();
            }

            $indexes = collect(Schema::getIndexes('affiliate_links'))->pluck('name');
            if (! $indexes->contains('affiliate_links_affiliate_id_inventory_id_unique')) {
                Schema::table('affiliate_links', function (Blueprint $table) {
                    $table->unique(['affiliate_id', 'inventory_id'], 'affiliate_links_affiliate_id_inventory_id_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('affiliate_commissions')) {
            Schema::table('affiliate_commissions', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('affiliate_commissions'))->pluck('name');
                if ($indexes->contains('affiliate_commissions_order_id_unique')) {
                    $table->dropUnique('affiliate_commissions_order_id_unique');
                }
            });
        }

        if (Schema::hasTable('affiliate_links')) {
            Schema::table('affiliate_links', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('affiliate_links'))->pluck('name');
                if ($indexes->contains('affiliate_links_affiliate_id_inventory_id_unique')) {
                    $table->dropUnique('affiliate_links_affiliate_id_inventory_id_unique');
                }
            });
        }
    }
};
