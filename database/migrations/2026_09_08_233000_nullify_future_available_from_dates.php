<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class NullifyFutureAvailableFromDates extends Migration
{
    /**
     * available_from scheduling is removed — force all listings live.
     *
     * @return void
     */
    public function up()
    {
        DB::table('inventories')
            ->whereNotNull('available_from')
            ->where('available_from', '>', now())
            ->update(['available_from' => now()->subMinute()]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Irreversible data fix.
    }
}
