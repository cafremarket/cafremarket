<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translation table for the old leaf Category (now SubCategory).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('translation_categories') && Schema::hasColumn('translation_categories', 'category_id')) {
            Schema::table('translation_categories', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });

            Schema::table('translation_categories', function (Blueprint $table) {
                $table->renameColumn('category_id', 'sub_category_id');
            });
        }

        if (Schema::hasTable('translation_categories') && ! Schema::hasTable('translation_sub_categories')) {
            Schema::rename('translation_categories', 'translation_sub_categories');
        }

        if (Schema::hasTable('translation_sub_categories') && Schema::hasColumn('translation_sub_categories', 'sub_category_id')) {
            Schema::table('translation_sub_categories', function (Blueprint $table) {
                $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('translation_sub_categories') && Schema::hasColumn('translation_sub_categories', 'sub_category_id')) {
            Schema::table('translation_sub_categories', function (Blueprint $table) {
                $table->dropForeign(['sub_category_id']);
            });
        }

        if (Schema::hasTable('translation_sub_categories') && ! Schema::hasTable('translation_categories')) {
            Schema::rename('translation_sub_categories', 'translation_categories');
        }

        if (Schema::hasTable('translation_categories') && Schema::hasColumn('translation_categories', 'sub_category_id')) {
            Schema::table('translation_categories', function (Blueprint $table) {
                $table->renameColumn('sub_category_id', 'category_id');
            });
        }
    }
};
