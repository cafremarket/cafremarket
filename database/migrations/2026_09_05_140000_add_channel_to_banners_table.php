<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'channel')) {
                $table->string('channel', 16)->default('web')->after('shop_id')->index();
            }
        });

        // Existing platform banners stay on the website; shop banners stay generic for shops.
        if (Schema::hasColumn('banners', 'channel')) {
            DB::table('banners')->whereNull('shop_id')->update(['channel' => 'web']);
        }
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (Schema::hasColumn('banners', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};
