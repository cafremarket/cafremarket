<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsChatQuoteToOrdersTable extends Migration
{
    /**
     * A chat-created custom order awaiting the customer's own choice of
     * payment method — kept out of the vendor panel's regular "unpaid
     * orders" list/count until the customer actually pays.
     */
    public function up()
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'is_chat_quote')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('is_chat_quote')->default(false)->after('payment_status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'is_chat_quote')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('is_chat_quote');
            });
        }
    }
}
