<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('affiliates') && ! Schema::hasColumn('affiliates', 'pay_to')) {
            Schema::table('affiliates', function (Blueprint $table) {
                $table->text('pay_to')->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('affiliates') && Schema::hasColumn('affiliates', 'pay_to')) {
            Schema::table('affiliates', function (Blueprint $table) {
                $table->dropColumn('pay_to');
            });
        }
    }
};
