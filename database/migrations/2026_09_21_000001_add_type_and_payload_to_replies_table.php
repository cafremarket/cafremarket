<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeAndPayloadToRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('replies') || Schema::hasColumn('replies', 'type')) {
            return;
        }

        Schema::table('replies', function (Blueprint $table) {
            $table->string('type')->default('text')->after('reply');
            $table->json('payload')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('replies') || ! Schema::hasColumn('replies', 'type')) {
            return;
        }

        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn(['type', 'payload']);
        });
    }
}
