<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslationAuditService
{
    protected array $langRoots = [];

    public function __construct()
    {
        $this->langRoots = [
            'core' => resource_path('lang'),
        ];

        foreach (glob(base_path('packages/*/resources/lang'), GLOB_ONLYDIR) ?: [] as $packageLangPath) {
            $package = basename(dirname(dirname($packageLangPath)));
            $this->langRoots['package:'.$package] = $packageLangPath;
        }
    }

    public function localesFor(string $root): array
    {
        if (! is_dir($root)) {
            return [];
        }

        return collect(File::directories($root))
            ->map(fn ($path) => basename($path))
            ->filter(fn ($code) => ! in_array($code, ['vendor', 'error_log'], true))
            ->values()
            ->all();
    }

    public function filesFor(string $root, string $locale): array
    {
        $path = $root.DIRECTORY_SEPARATOR.$locale;

        if (! is_dir($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->all();
    }

    public function loadFile(string $root, string $locale, string $file): array
    {
        $path = $root.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$file.'.php';

        if (! is_file($path)) {
            return [];
        }

        try {
            $data = include $path;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to load translation file [{$path}]: {$e->getMessage()}", 0, $e);
        }

        return is_array($data) ? $data : [];
    }

    public function safeLoadFile(string $root, string $locale, string $file): array
    {
        try {
            return $this->loadFile($root, $locale, $file);
        } catch (\Throwable) {
            return [];
        }
    }

    public function flatten(array $items, string $prefix = ''): array
    {
        $flat = [];

        foreach ($items as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $flat += $this->flatten($value, $fullKey);
            } else {
                $flat[$fullKey] = $value;
            }
        }

        return $flat;
    }

    public function unflatten(array $flat): array
    {
        $nested = [];

        foreach ($flat as $key => $value) {
            data_set($nested, $key, $value);
        }

        return $nested;
    }

    public function compareLocaleFiles(string $baseLocale, string $targetLocale, ?string $rootKey = null): array
    {
        $report = [
            'missing_files' => [],
            'missing_keys' => [],
            'extra_keys' => [],
            'empty_values' => [],
            'parse_errors' => [],
            'totals' => [
                'base_keys' => 0,
                'target_keys' => 0,
                'missing' => 0,
                'extra' => 0,
                'empty' => 0,
            ],
        ];

        foreach ($this->langRoots as $name => $root) {
            if ($rootKey && $rootKey !== $name) {
                continue;
            }

            $baseFiles = $this->filesFor($root, $baseLocale);
            $targetFiles = $this->filesFor($root, $targetLocale);

            foreach (array_diff($baseFiles, $targetFiles) as $file) {
                $report['missing_files'][] = "{$name}:{$file}.php";
            }

            foreach ($baseFiles as $file) {
                $targetPath = $root.DIRECTORY_SEPARATOR.$targetLocale.DIRECTORY_SEPARATOR.$file.'.php';

                try {
                    $baseFlat = $this->flatten($this->loadFile($root, $baseLocale, $file));
                } catch (\Throwable $e) {
                    $report['parse_errors'][] = [
                        'group' => $name,
                        'file' => $file,
                        'locale' => $baseLocale,
                        'message' => $e->getMessage(),
                    ];

                    continue;
                }

                if (! is_file($targetPath)) {
                    foreach ($baseFlat as $key => $baseValue) {
                        $report['missing_keys'][] = [
                            'group' => $name,
                            'file' => $file,
                            'key' => $key,
                            'base' => $baseValue,
                        ];
                    }

                    $report['totals']['base_keys'] += count($baseFlat);

                    continue;
                }

                try {
                    $targetFlat = $this->flatten($this->loadFile($root, $targetLocale, $file));
                } catch (\Throwable $e) {
                    $report['parse_errors'][] = [
                        'group' => $name,
                        'file' => $file,
                        'locale' => $targetLocale,
                        'message' => $e->getMessage(),
                    ];

                    foreach ($baseFlat as $key => $baseValue) {
                        $report['missing_keys'][] = [
                            'group' => $name,
                            'file' => $file,
                            'key' => $key,
                            'base' => $baseValue,
                        ];
                    }

                    $report['totals']['base_keys'] += count($baseFlat);

                    continue;
                }

                $report['totals']['base_keys'] += count($baseFlat);
                $report['totals']['target_keys'] += count($targetFlat);

                foreach (array_diff(array_keys($baseFlat), array_keys($targetFlat)) as $key) {
                    $report['missing_keys'][] = [
                        'group' => $name,
                        'file' => $file,
                        'key' => $key,
                        'base' => $baseFlat[$key],
                    ];
                }

                foreach (array_diff(array_keys($targetFlat), array_keys($baseFlat)) as $key) {
                    $report['extra_keys'][] = [
                        'group' => $name,
                        'file' => $file,
                        'key' => $key,
                        'target' => $targetFlat[$key],
                    ];
                }

                foreach ($baseFlat as $key => $baseValue) {
                    if (! array_key_exists($key, $targetFlat)) {
                        continue;
                    }

                    $targetValue = $targetFlat[$key];

                    if ($targetValue === null || $targetValue === '') {
                        $report['empty_values'][] = [
                            'group' => $name,
                            'file' => $file,
                            'key' => $key,
                            'base' => $baseValue,
                        ];
                    }
                }
            }
        }

        $report['totals']['missing'] = count($report['missing_keys']);
        $report['totals']['extra'] = count($report['extra_keys']);
        $report['totals']['empty'] = count($report['empty_values']);

        return $report;
    }

    public function scanCodebaseForTranslationKeys(array $paths = []): array
    {
        $paths = $paths ?: [
            app_path(),
            resource_path('views'),
            public_path('themes'),
            base_path('packages'),
            base_path('routes'),
        ];

        $patterns = [
            "/trans\\(\\s*['\\\"]([^'\\\"]+)['\\\"]/",
            "/__\\(\\s*['\\\"]([^'\\\"]+)['\\\"]/",
            "/@lang\\(\\s*['\\\"]([^'\\\"]+)['\\\"]/",
            "/trans_choice\\(\\s*['\\\"]([^'\\\"]+)['\\\"]/",
        ];

        $keys = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if (! in_array($file->getExtension(), ['php', 'blade.php', 'js'], true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $contents, $matches)) {
                        foreach ($matches[1] as $key) {
                            if (Str::contains($key, '${') || Str::contains($key, '{{')) {
                                continue;
                            }

                            if (! $this->looksLikeTranslationKey($key)) {
                                continue;
                            }

                            $keys[$key][] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                        }
                    }
                }
            }
        }

        ksort($keys);

        return $keys;
    }

    public function findUndefinedKeys(string $locale, array $usedKeys, string $baseLocale = 'en'): array
    {
        $undefined = [];

        foreach ($usedKeys as $key => $files) {
            if ($this->translationKeyExists($key, $locale, $baseLocale)) {
                continue;
            }

            $undefined[$key] = array_values(array_unique($files));
        }

        return $undefined;
    }

    public function translationKeyExists(string $key, string $locale, string $baseLocale = 'en'): bool
    {
        foreach ($this->resolveTranslationKey($key) as $candidate) {
            $roots = $candidate['roots'] ?? $this->langRoots;

            foreach ($roots as $root) {
                $flat = $this->flatten($this->safeLoadFile($root, $locale, $candidate['file']));

                if (array_key_exists($candidate['item'], $flat)) {
                    return true;
                }

                $baseFlat = $this->flatten($this->safeLoadFile($root, $baseLocale, $candidate['file']));

                if (array_key_exists($candidate['item'], $baseFlat)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, array{file: string, item: string, roots?: array<string, string>}>
     */
    protected function resolveTranslationKey(string $key): array
    {
        if (Str::contains($key, '::')) {
            [$namespace, $remainder] = explode('::', $key, 2);
            [$file, $item] = array_pad(explode('.', $remainder, 2), 2, null);

            if (! $file || ! $item) {
                return [];
            }

            $packageRoot = $this->packageLangRootForNamespace($namespace);

            return [[
                'file' => $file,
                'item' => $item,
                'roots' => $packageRoot ? ['package' => $packageRoot] : $this->langRoots,
            ]];
        }

        [$file, $item] = array_pad(explode('.', $key, 2), 2, null);

        if (! $file || ! $item) {
            return [];
        }

        return [['file' => $file, 'item' => $item]];
    }

    protected function packageLangRootForNamespace(string $namespace): ?string
    {
        $needle = Str::lower($namespace);

        foreach (glob(base_path('packages/*/resources/lang'), GLOB_ONLYDIR) ?: [] as $packageLangPath) {
            $package = basename(dirname(dirname($packageLangPath)));

            if (Str::lower($package) === $needle) {
                return $packageLangPath;
            }
        }

        return null;
    }

    public function fillMissingKeys(string $baseLocale, string $targetLocale, bool $dryRun = true): array
    {
        $report = $this->compareLocaleFiles($baseLocale, $targetLocale);
        $written = [];

        if ($dryRun) {
            return $report;
        }

        foreach ($report['missing_files'] as $missingFile) {
            [$group, $filename] = explode(':', $missingFile, 2);
            $file = str_replace('.php', '', $filename);
            $root = $this->langRoots[$group] ?? null;

            if (! $root) {
                continue;
            }

            $baseData = $this->loadFile($root, $baseLocale, $file);
            $targetPath = $root.DIRECTORY_SEPARATOR.$targetLocale.DIRECTORY_SEPARATOR.$file.'.php';
            File::ensureDirectoryExists(dirname($targetPath));
            File::put($targetPath, $this->exportPhpArray($baseData));
            $written[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $targetPath);
        }

        $grouped = collect($report['missing_keys'])->groupBy(fn ($row) => $row['group'].':'.$row['file']);

        foreach ($grouped as $groupFile => $rows) {
            [$group, $file] = explode(':', $groupFile, 2);
            $root = $this->langRoots[$group] ?? null;

            if (! $root) {
                continue;
            }

            $targetPath = $root.DIRECTORY_SEPARATOR.$targetLocale.DIRECTORY_SEPARATOR.$file.'.php';
            $current = $this->loadFile($root, $targetLocale, $file);
            $flat = $this->flatten($current);

            foreach ($rows as $row) {
                $flat[$row['key']] = $row['base'];
            }

            $content = $this->exportPhpArray($this->unflatten($flat));
            File::ensureDirectoryExists(dirname($targetPath));
            File::put($targetPath, $content);
            $written[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $targetPath);
        }

        $report['written_files'] = $written;

        return $report;
    }

    protected function looksLikeTranslationKey(string $key): bool
    {
        if (Str::contains($key, '::')) {
            return Str::contains($key, '.');
        }

        if (Str::contains($key, ' ')) {
            return false;
        }

        return Str::contains($key, '.');
    }

    protected function exportPhpArray(array $array, int $depth = 1): string
    {
        $indent = str_repeat('    ', $depth);
        $rootIndent = str_repeat('    ', $depth - 1);
        $lines = ["<?php\n\nreturn ["];

        foreach ($array as $key => $value) {
            $exportedKey = is_int($key) ? $key : "'".addslashes((string) $key)."'";

            if (is_array($value)) {
                $lines[] = $indent.$exportedKey.' => '.$this->exportArrayBody($value, $depth + 1).',';
            } else {
                $lines[] = $indent.$exportedKey.' => '.$this->exportScalar($value).',';
            }
        }

        $lines[] = $rootIndent.'];';
        $lines[] = '';

        return implode("\n", $lines);
    }

    protected function exportArrayBody(array $array, int $depth): string
    {
        if ($array === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $close = str_repeat('    ', $depth - 1);
        $lines = ['['];

        foreach ($array as $key => $value) {
            $exportedKey = is_int($key) ? $key : "'".addslashes((string) $key)."'";

            if (is_array($value)) {
                $lines[] = $indent.$exportedKey.' => '.$this->exportArrayBody($value, $depth + 1).',';
            } else {
                $lines[] = $indent.$exportedKey.' => '.$this->exportScalar($value).',';
            }
        }

        $lines[] = $close.']';

        return implode("\n", $lines);
    }

    protected function exportScalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return 'null';
        }

        return var_export((string) $value, true);
    }
}
