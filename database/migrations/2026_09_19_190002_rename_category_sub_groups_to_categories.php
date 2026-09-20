<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 2 of the category system collapse (3-level -> 2-level).
 *
 * The old `category_sub_groups` table is promoted to be the new top-level
 * `categories` table. The old `category_group_id` FK is dropped (the
 * CategoryGroup level is retired in a later migration) and a `featured`
 * flag is added — this is the new homepage "featured categories" toggle.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('category_sub_groups') && Schema::hasColumn('category_sub_groups', 'category_group_id')) {
            Schema::table('category_sub_groups', function (Blueprint $table) {
                $table->dropForeign(['category_group_id']);
            });

            Schema::table('category_sub_groups', function (Blueprint $table) {
                $table->dropColumn('category_group_id');
            });
        }

        if (Schema::hasTable('category_sub_groups') && ! Schema::hasColumn('category_sub_groups', 'featured')) {
            Schema::table('category_sub_groups', function (Blueprint $table) {
                $table->boolean('featured')->default(0)->after('active');
            });
        }

        if (Schema::hasTable('category_sub_groups') && ! Schema::hasTable('categories')) {
            Schema::rename('category_sub_groups', 'categories');
        }

        if (Schema::hasTable('sub_categories') && Schema::hasColumn('sub_categories', 'category_id') && Schema::hasTable('categories')) {
            Schema::table('sub_categories', function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sub_categories') && Schema::hasColumn('sub_categories', 'category_id')) {
            Schema::table('sub_categories', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
        }

        if (Schema::hasTable('categories') && ! Schema::hasTable('category_sub_groups')) {
            Schema::rename('categories', 'category_sub_groups');
        }

        if (Schema::hasTable('category_sub_groups') && Schema::hasColumn('category_sub_groups', 'featured')) {
            Schema::table('category_sub_groups', function (Blueprint $table) {
                $table->dropColumn('featured');
            });
        }

        if (Schema::hasTable('category_sub_groups') && ! Schema::hasColumn('category_sub_groups', 'category_group_id')) {
            Schema::table('category_sub_groups', function (Blueprint $table) {
                $table->integer('category_group_id')->unsigned()->nullable();
            });
        }
    }
};
