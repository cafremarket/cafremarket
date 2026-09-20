<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Step 3 of the category system collapse (3-level -> 2-level).
 *
 * The top-level CategoryGroup layer has no equivalent in the new 2-level
 * model (its child, CategorySubGroup, was already promoted to be the new
 * top-level Category in the previous migration). This data-loss is
 * intentional and was confirmed with the store owner; group names/rows are
 * exported to a JSON backup file before the table is dropped so they are
 * recoverable if anyone needs to consult the old grouping later.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('category_groups')) {
            $rows = DB::table('category_groups')->get();

            Storage::disk('local')->put(
                'backups/category_groups_'.now()->format('Y_m_d_His').'.json',
                $rows->toJson(JSON_PRETTY_PRINT)
            );

            // Other tables (e.g. translation_category_groups, dropped later in
            // this same migration run) still hold FKs pointing at this table —
            // drop those constraints first or MySQL refuses the table drop.
            $this->dropForeignKeysReferencing('category_groups');

            Schema::dropIfExists('category_groups');
        }
    }

    /**
     * Drop every FK constraint (on any table) that references $table, so it
     * can be safely dropped.
     */
    private function dropForeignKeysReferencing(string $table): void
    {
        $constraints = DB::select(
            'SELECT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = ?',
            [$table]
        );

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE `{$constraint->TABLE_NAME}` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
        }
    }

    /**
     * Recreates an empty table only — the dropped rows are not restorable
     * from here, only from the JSON backup written in up().
     */
    public function down(): void
    {
        if (! Schema::hasTable('category_groups')) {
            Schema::create('category_groups', function ($table) {
                $table->increments('id');
                $table->string('name', 200);
                $table->string('slug', 200)->unique();
                $table->text('description')->nullable();
                $table->string('icon', 100)->default('cube')->nullable();
                $table->boolean('active')->default(1);
                $table->integer('order')->default(100)->nullable();
                $table->text('meta_title')->nullable();
                $table->longText('meta_description')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }
};
