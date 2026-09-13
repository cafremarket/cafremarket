<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('popups') && ! Schema::hasColumn('popups', 'hide_text')) {
            Schema::table('popups', function (Blueprint $table) {
                $table->boolean('hide_text')->default(false)->after('bg_color');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('popups') && Schema::hasColumn('popups', 'hide_text')) {
            Schema::table('popups', function (Blueprint $table) {
                $table->dropColumn('hide_text');
            });
        }
    }
};
