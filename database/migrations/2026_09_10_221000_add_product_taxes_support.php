<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            if (! Schema::hasColumn('taxes', 'type')) {
                $table->string('type', 20)->default('percent')->after('taxrate');
            }
        });

        if (! Schema::hasTable('product_tax')) {
            Schema::create('product_tax', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedInteger('tax_id');
                $table->timestamps();

                $table->unique(['product_id', 'tax_id']);
                $table->index('tax_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_tax');

        Schema::table('taxes', function (Blueprint $table) {
            if (Schema::hasColumn('taxes', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
