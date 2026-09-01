<?php

namespace App\Console\Commands;

use App\Models\Language;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeduplicatePortugueseLanguageCommand extends Command
{
    protected $signature = 'languages:dedupe-portuguese';

    protected $description = 'Remove duplicate Portuguese language rows and keep a single pt entry';

    public function handle(): int
    {
        if (! Schema::hasTable('languages')) {
            $this->error('languages table not found.');

            return self::FAILURE;
        }

        $keepCode = 'pt';
        $duplicateIds = Language::withTrashed()
            ->where('code', '!=', $keepCode)
            ->where(function ($query) {
                $query->where('language', 'Portuguese')
                    ->orWhere('language', 'like', '%Portuguese%')
                    ->orWhere('code', 'like', 'pt%')
                    ->orWhere('code', 'like', 'pt-%')
                    ->orWhere('code', 'like', 'pt_%');
            })
            ->pluck('code', 'id');

        if ($duplicateIds->isEmpty()) {
            $this->info('No duplicate Portuguese language rows found.');
        } else {
            foreach ($duplicateIds as $id => $code) {
                Language::withTrashed()->where('id', $id)->forceDelete();
                $this->line("  Removed duplicate: {$code} (id {$id})");
            }
        }

        $portuguese = Language::withTrashed()->where('code', $keepCode)->first();

        if ($portuguese) {
            if ($portuguese->trashed()) {
                $portuguese->restore();
            }

            $portuguese->update([
                'php_locale_code' => 'pt_PT',
                'language' => 'Portuguese',
                'active' => true,
            ]);

            $this->line("  Kept: {$keepCode} (Portuguese)");
        } else {
            Language::create([
                'code' => $keepCode,
                'php_locale_code' => 'pt_PT',
                'language' => 'Portuguese',
                'order' => 100,
                'rtl' => false,
                'active' => true,
            ]);

            $this->line("  Created missing: {$keepCode} (Portuguese)");
        }

        if (Schema::hasTable('systems')) {
            $updated = DB::table('systems')
                ->where('default_language', '!=', $keepCode)
                ->where(function ($query) {
                    $query->where('default_language', 'like', 'pt%')
                        ->orWhere('default_language', 'like', 'pt-%')
                        ->orWhere('default_language', 'like', 'pt_%');
                })
                ->update(['default_language' => $keepCode]);

            if ($updated) {
                $this->line("  Updated {$updated} system row(s) to default_language={$keepCode}");
            }
        }

        Cache::forget('active_locales');

        $this->info('Portuguese language deduplication complete.');

        return self::SUCCESS;
    }
}
