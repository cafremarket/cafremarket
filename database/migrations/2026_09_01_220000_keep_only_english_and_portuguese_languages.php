<?php

use App\Models\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $keepCodes = ['en', 'pt'];

    public function up(): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        Language::withTrashed()
            ->where(function ($query) {
                $query->whereNotIn('code', $this->keepCodes)
                    ->orWhere(function ($portuguese) {
                        $portuguese->where('code', '!=', 'pt')
                            ->where(function ($q) {
                                $q->where('language', 'Portuguese')
                                    ->orWhere('language', 'like', '%Portuguese%')
                                    ->orWhere('code', 'like', 'pt%')
                                    ->orWhere('code', 'like', 'pt-%')
                                    ->orWhere('code', 'like', 'pt_%');
                            });
                    });
            })
            ->forceDelete();

        $now = now();

        Language::withTrashed()->updateOrCreate(
            ['code' => 'en'],
            [
                'php_locale_code' => 'en_US',
                'language' => 'English',
                'order' => 1,
                'rtl' => false,
                'active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        Language::withTrashed()->updateOrCreate(
            ['code' => 'pt'],
            [
                'php_locale_code' => 'pt_PT',
                'language' => 'Portuguese',
                'order' => 2,
                'rtl' => false,
                'active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        if (Schema::hasTable('systems')) {
            DB::table('systems')
                ->whereNotIn('default_language', $this->keepCodes)
                ->update(['default_language' => 'en']);
        }

        Cache::forget('active_locales');
    }

    public function down(): void
    {
        // Languages are not restored automatically.
    }
};
