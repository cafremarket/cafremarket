<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Translations for the retired CategoryGroup level. Backed up alongside
 * category_groups itself (see 2026_09_19_190003) before being dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('translation_category_groups')) {
            $rows = DB::table('translation_category_groups')->get();

            Storage::disk('local')->put(
                'backups/translation_category_groups_'.now()->format('Y_m_d_His').'.json',
                $rows->toJson(JSON_PRETTY_PRINT)
            );

            Schema::dropIfExists('translation_category_groups');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('translation_category_groups')) {
            Schema::create('translation_category_groups', function ($table) {
                $table->id();
                $table->unsignedInteger('category_group_id');
                $table->string('lang');
                $table->text('translation');
                $table->timestamps();
            });
        }
    }
};
