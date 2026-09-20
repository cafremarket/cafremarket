<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'categories',
        'sub_categories',
        'banners',
        'sliders',
        'attributes',
        'attribute_values',
        'languages',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'order')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('order');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'order')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->integer('order')->unsigned()->nullable()->default(100);
                });
            }
        }
    }
};
