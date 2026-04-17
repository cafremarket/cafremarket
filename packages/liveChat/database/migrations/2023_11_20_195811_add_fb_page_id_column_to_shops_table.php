<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFbPageIdColumnToShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('shops', ['fb_page_id'])) {
            Schema::table('shops', function (Blueprint $table) {
                $table->string('fb_page_id')->after('email')->nullable();
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
        if (Schema::hasColumns('shops', ['fb_page_id'])) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropColumn('fb_page_id');
            });
        }
    }
}
