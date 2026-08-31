<?php
/**
 * Remove duplicate admin-card class names from double migration.
 * Run: php scripts/fix_duplicate_classes.php
 */

$basePath = dirname(__DIR__);
$dirs = [
    $basePath . '/resources/views',
    $basePath . '/packages',
];

$patterns = [
    'admin-card__header admin-card__header' => 'admin-card__header',
    'admin-card__title admin-card__title' => 'admin-card__title',
    'admin-card__actions admin-card__actions' => 'admin-card__actions',
    'admin-card__body admin-card__body' => 'admin-card__body',
    'admin-table admin-table' => 'admin-table',
];

$fixed = 0;

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $fileInfo) {
        if (!str_ends_with($fileInfo->getFilename(), '.blade.php')) {
            continue;
        }
        $path = $fileInfo->getPathname();
        $content = file_get_contents($path);
        $original = $content;
        foreach ($patterns as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        if ($content !== $original) {
            file_put_contents($path, $content);
            $fixed++;
        }
    }
}

echo "Fixed {$fixed} files with duplicate classes." . PHP_EOL;
