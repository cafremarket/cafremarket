<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'length')) {
                $table->decimal('length', 10, 4)->nullable()->change();
            }
            if (Schema::hasColumn('inventories', 'width')) {
                $table->decimal('width', 10, 4)->nullable()->change();
            }
            if (Schema::hasColumn('inventories', 'height')) {
                $table->decimal('height', 10, 4)->nullable()->change();
            }
            if (Schema::hasColumn('inventories', 'distance_unit')) {
                $table->string('distance_unit')->nullable()->default('cm')->change();
            }
        });
    }

    public function down(): void
    {
        // Keep nullable — reversing to NOT NULL would break existing null rows.
    }
};
