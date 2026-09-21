<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affiliates') || ! Schema::hasColumn('affiliates', 'username')) {
            return;
        }

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('affiliates') || Schema::hasColumn('affiliates', 'username')) {
            return;
        }

        Schema::table('affiliates', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });
    }
};
