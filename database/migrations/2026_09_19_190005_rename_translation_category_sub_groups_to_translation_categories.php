<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translation table for the old CategorySubGroup (now the new top-level Category).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('translation_category_sub_groups') && Schema::hasColumn('translation_category_sub_groups', 'category_sub_group_id')) {
            Schema::table('translation_category_sub_groups', function (Blueprint $table) {
                $table->dropForeign(['category_sub_group_id']);
            });

            Schema::table('translation_category_sub_groups', function (Blueprint $table) {
                $table->renameColumn('category_sub_group_id', 'category_id');
            });
        }

        if (Schema::hasTable('translation_category_sub_groups') && ! Schema::hasTable('translation_categories')) {
            Schema::rename('translation_category_sub_groups', 'translation_categories');
        }

        if (Schema::hasTable('translation_categories') && Schema::hasColumn('translation_categories', 'category_id')) {
            Schema::table('translation_categories', function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('translation_categories') && Schema::hasColumn('translation_categories', 'category_id')) {
            Schema::table('translation_categories', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
        }

        if (Schema::hasTable('translation_categories') && ! Schema::hasTable('translation_category_sub_groups')) {
            Schema::rename('translation_categories', 'translation_category_sub_groups');
        }

        if (Schema::hasTable('translation_category_sub_groups') && Schema::hasColumn('translation_category_sub_groups', 'category_id')) {
            Schema::table('translation_category_sub_groups', function (Blueprint $table) {
                $table->renameColumn('category_id', 'category_sub_group_id');
            });
        }
    }
};
