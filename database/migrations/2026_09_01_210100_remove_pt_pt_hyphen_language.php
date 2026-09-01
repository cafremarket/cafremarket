<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        $duplicateCodes = ['pt-PT', 'pt_PT'];

        if (Schema::hasTable('systems')) {
            DB::table('systems')
                ->whereIn('default_language', $duplicateCodes)
                ->update(['default_language' => 'pt']);
        }

        DB::table('languages')->whereIn('code', $duplicateCodes)->delete();

        DB::table('languages')
            ->where('code', 'pt')
            ->update([
                'php_locale_code' => 'pt_PT',
                'language' => 'Portuguese',
                'updated_at' => now(),
            ]);

        Cache::forget('active_locales');
    }

    public function down(): void
    {
        // No rollback — duplicate Portuguese entries are not restored.
    }
};
