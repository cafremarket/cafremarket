<?php

namespace App\Console\Commands;

use App\Services\Translation\TranslationAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncUndefinedTranslationsCommand extends Command
{
    protected $signature = 'translations:sync-undefined
                            {--base=en : Base locale}
                            {--locale=pt : Target locale(s), comma-separated}
                            {--dry-run : Preview keys that would be added}';

    protected $description = 'Add translation keys used in code but missing from lang files';

    public function handle(TranslationAuditService $audit): int
    {
        $baseLocale = (string) $this->option('base');
        $targets = array_filter(array_map('trim', explode(',', (string) $this->option('locale'))));
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — no files will be modified.');
        }

        $result = $audit->syncUndefinedKeys($baseLocale, $targets, $dryRun);
        $locales = array_values(array_unique(array_merge([$baseLocale], $targets)));

        foreach ($locales as $locale) {
            $added = $result['added'][$locale] ?? [];
            $this->info(($dryRun ? 'Would add' : 'Added').' '.count($added)." keys to {$locale}:");

            foreach (array_slice($added, 0, 40, true) as $key => $value) {
                $this->line("  - {$key} => {$value}");
            }

            if (count($added) > 40) {
                $this->line('  ...');
            }
        }

        if ($result['skipped']) {
            $this->comment('Skipped '.count($result['skipped']).' dynamic or unavailable package keys.');
        }

        if (! $dryRun) {
            Artisan::call('cache:clear');
            $this->comment('Translation cache cleared.');
        }

        return self::SUCCESS;
    }
}
