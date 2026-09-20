<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 1 of the category system collapse (3-level -> 2-level).
 *
 * The old leaf-level `categories` table (where products actually attach)
 * becomes the new `sub_categories` table. Every row keeps its primary key,
 * so `category_product`/`attribute_categories` pivot rows never need to move.
 *
 * Merchant-owned rows (shop_id NOT NULL, from the 2026-09-03 "merchant
 * catalog" migration) are folded in as normal, active, globally-visible
 * rows before the shop_id column is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categories') && ! Schema::hasTable('sub_categories')) {
            if (Schema::hasColumn('categories', 'shop_id')) {
                DB::table('categories')->whereNotNull('shop_id')->update(['active' => 1]);

                Schema::table('categories', function (Blueprint $table) {
                    $table->dropColumn('shop_id');
                });
            }

            Schema::rename('categories', 'sub_categories');
        }

        if (Schema::hasTable('sub_categories') && Schema::hasColumn('sub_categories', 'category_sub_group_id')) {
            // Renaming a table does not rename its FK constraints in MySQL, so the
            // constraint is still named after the original `categories` table —
            // look up the real name instead of guessing from the current table name.
            $this->dropForeignKeyIfExists('sub_categories', 'category_sub_group_id');

            Schema::table('sub_categories', function (Blueprint $table) {
                $table->renameColumn('category_sub_group_id', 'category_id');
            });
        }
    }

    /**
     * Drop whatever the actual foreign key constraint on $table.$column is
     * named, without assuming it follows the current table's naming
     * convention (renamed tables keep their original constraint names).
     */
    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $constraint = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$table, $column]
        );

        if ($constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sub_categories') && Schema::hasColumn('sub_categories', 'category_id')) {
            Schema::table('sub_categories', function (Blueprint $table) {
                $table->renameColumn('category_id', 'category_sub_group_id');
            });
        }

        if (Schema::hasTable('sub_categories') && ! Schema::hasTable('categories')) {
            Schema::rename('sub_categories', 'categories');
        }

        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'shop_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedInteger('shop_id')->nullable()->after('id')->index();
            });
        }
    }
};
