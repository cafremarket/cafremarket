<?php
/**
 * Fix corrupted box-* class names from migrate_admin_views.php bug.
 * Run: php scripts/fix_admin_card_classes.php
 */

$basePath = dirname(__DIR__);
$dirs = [
    $basePath . '/resources/views/admin',
    $basePath . '/packages',
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

        $content = str_replace('box admin-card-header with-border', 'box-header with-border admin-card__header', $content);
        $content = str_replace('box admin-card-header', 'box-header with-border admin-card__header', $content);
        $content = str_replace('box admin-card-body', 'box-body admin-card__body', $content);
        $content = str_replace('box admin-card-tools pull-right', 'box-tools pull-right admin-card__actions', $content);
        $content = str_replace('box admin-card-tools', 'box-tools pull-right admin-card__actions', $content);

        if ($content !== $original) {
            file_put_contents($path, $content);
            $fixed++;
            echo 'FIX ' . str_replace($basePath . DIRECTORY_SEPARATOR, '', $path) . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Fixed {$fixed} files." . PHP_EOL;
