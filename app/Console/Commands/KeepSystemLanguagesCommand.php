<?php

namespace App\Console\Commands;

use App\Models\Language;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KeepSystemLanguagesCommand extends Command
{
    protected $signature = 'languages:keep-en-pt';

    protected $description = 'Keep only English and Portuguese languages in the system';

    protected array $keepCodes = ['en', 'pt'];

    public function handle(): int
    {
        if (! Schema::hasTable('languages')) {
            $this->error('languages table not found.');

            return self::FAILURE;
        }

        $removed = Language::withTrashed()
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
            ->get();

        foreach ($removed as $language) {
            $language->forceDelete();
            $this->line("  Removed: {$language->code} ({$language->language})");
        }

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
            ]
        );

        if (Schema::hasTable('systems')) {
            $updated = DB::table('systems')
                ->whereNotIn('default_language', $this->keepCodes)
                ->update(['default_language' => 'en']);

            if ($updated) {
                $this->line("  Reset {$updated} system default_language value(s) to en");
            }
        }

        Cache::forget('active_locales');

        $this->info('System languages limited to English and Portuguese.');

        return self::SUCCESS;
    }
}
