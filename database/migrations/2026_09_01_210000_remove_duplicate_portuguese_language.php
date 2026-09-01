<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        if (Schema::hasTable('systems')) {
            DB::table('systems')
                ->where('default_language', 'pt_PT')
                ->update(['default_language' => 'pt']);
        }

        DB::table('languages')->where('code', 'pt_PT')->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        $exists = DB::table('languages')->where('code', 'pt_PT')->exists();

        if (! $exists) {
            DB::table('languages')->insert([
                'code' => 'pt_PT',
                'php_locale_code' => 'pt_PT',
                'language' => 'Portuguese',
                'order' => 100,
                'rtl' => 0,
                'active' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
