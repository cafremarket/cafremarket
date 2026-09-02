<?php

namespace App\Console\Commands;

use App\Services\Translation\TranslationAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckMissingTranslationsCommand extends Command
{
    protected $signature = 'translations:check
                            {--base=en : Base locale to compare against}
                            {--locale=pt : Target locale(s), comma-separated}
                            {--scan-code : Also scan PHP/Blade/JS for translation keys missing from lang files}
                            {--export= : Optional path to export JSON report}';

    protected $description = 'Audit missing translation keys across the system';

    public function handle(TranslationAuditService $audit): int
    {
        $baseLocale = (string) $this->option('base');
        $targets = array_filter(array_map('trim', explode(',', (string) $this->option('locale'))));

        if (empty($targets)) {
            $this->error('Provide at least one target locale using --locale=pt');

            return self::FAILURE;
        }

        $fullReport = [
            'base' => $baseLocale,
            'targets' => [],
            'code_scan' => null,
        ];

        foreach ($targets as $targetLocale) {
            $this->info("Comparing {$baseLocale} → {$targetLocale}");
            $report = $audit->compareLocaleFiles($baseLocale, $targetLocale);
            $fullReport['targets'][$targetLocale] = $report;

            $this->line('  Missing files: '.count($report['missing_files']));
            $this->line("  Missing keys: {$report['totals']['missing']}");
            $this->line("  Empty values: {$report['totals']['empty']}");
            $this->line("  Extra keys: {$report['totals']['extra']}");

            if ($report['parse_errors'] ?? []) {
                $this->error('  Parse errors: '.count($report['parse_errors']));
                foreach (array_slice($report['parse_errors'], 0, 10) as $error) {
                    $this->line("    - {$error['group']}:{$error['file']}.php ({$error['locale']})");
                }
            }

            if ($report['missing_files']) {
                $this->warn('  Missing translation files:');
                foreach ($report['missing_files'] as $file) {
                    $this->line("    - {$file}");
                }
            }

            if ($report['missing_keys']) {
                $this->warn('  Sample missing keys (first 25):');
                foreach (array_slice($report['missing_keys'], 0, 25) as $row) {
                    $this->line("    - {$row['group']}:{$row['file']}.{$row['key']}");
                }

                if (count($report['missing_keys']) > 25) {
                    $this->line('    ...');
                }
            }
        }

        if ($this->option('scan-code')) {
            $this->info('Scanning codebase for translation keys...');
            $usedKeys = $audit->scanCodebaseForTranslationKeys();
            $undefined = [];

            foreach ($targets as $targetLocale) {
                $undefined[$targetLocale] = $audit->findUndefinedKeys($targetLocale, $usedKeys, $baseLocale);
            }

            $fullReport['code_scan'] = [
                'used_keys' => count($usedKeys),
                'undefined' => $undefined,
            ];

            foreach ($targets as $targetLocale) {
                $count = count($undefined[$targetLocale]);
                $this->line("  Undefined keys used in code for {$targetLocale}: {$count}");

                foreach (array_slice($undefined[$targetLocale], 0, 15, true) as $key => $files) {
                    $this->line("    - {$key} (".count($files).' files)');
                }
            }
        }

        $exportPath = $this->option('export');

        if ($exportPath) {
            $path = str_starts_with($exportPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $exportPath)
                ? $exportPath
                : base_path($exportPath);

            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($fullReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Report exported to {$path}");
        }

        $hasMissing = collect($fullReport['targets'])->contains(fn ($report) => $report['totals']['missing'] > 0);

        if ($hasMissing) {
            $this->newLine();
            $this->comment('Run php artisan translations:fill-missing --locale=pt to copy missing English strings into Portuguese files.');
        $this->comment('Run php artisan translations:sync-undefined --locale=pt to add keys used in code but missing from lang files.');

            return self::FAILURE;
        }

        $this->info('No missing translation keys found.');

        return self::SUCCESS;
    }
}
