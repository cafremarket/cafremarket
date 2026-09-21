<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsChatCustomToProductsAndInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'is_chat_custom')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_chat_custom')->default(false)->after('active');
            });
        }

        if (Schema::hasTable('inventories') && ! Schema::hasColumn('inventories', 'is_chat_custom')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->boolean('is_chat_custom')->default(false)->after('active');
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
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'is_chat_custom')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_chat_custom');
            });
        }

        if (Schema::hasTable('inventories') && Schema::hasColumn('inventories', 'is_chat_custom')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('is_chat_custom');
            });
        }
    }
}
