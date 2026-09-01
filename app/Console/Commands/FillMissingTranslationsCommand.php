<?php

namespace App\Console\Commands;

use App\Services\Translation\TranslationAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FillMissingTranslationsCommand extends Command
{
    protected $signature = 'translations:fill-missing
                            {--base=en : Base locale}
                            {--locale=pt : Target locale}
                            {--dry-run : Show what would be written without saving files}';

    protected $description = 'Copy missing translation keys from the base locale into the target locale';

    public function handle(TranslationAuditService $audit): int
    {
        $baseLocale = (string) $this->option('base');
        $targetLocale = (string) $this->option('locale');
        $dryRun = (bool) $this->option('dry-run');

        $report = $audit->fillMissingKeys($baseLocale, $targetLocale, $dryRun);

        $missing = $report['totals']['missing'];

        if ($missing === 0) {
            $this->info("No missing keys between {$baseLocale} and {$targetLocale}.");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Would fill {$missing} missing keys into {$targetLocale}.");

            foreach (array_slice($report['missing_keys'], 0, 30) as $row) {
                $this->line("  - {$row['group']}:{$row['file']}.{$row['key']}");
            }

            if ($missing > 30) {
                $this->line('  ...');
            }

            return self::SUCCESS;
        }

        $written = $report['written_files'] ?? [];

        $this->info("Filled {$missing} missing keys into {$targetLocale}.");
        $this->line('Updated files:');

        foreach ($written as $file) {
            $this->line("  - {$file}");
        }

        Artisan::call('cache:clear');

        $this->comment('Translation cache cleared.');

        return self::SUCCESS;
    }
}
