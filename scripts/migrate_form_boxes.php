<?php
/**
 * Add admin-card classes to form partials and embedded boxes.
 * Run: php scripts/migrate_form_boxes.php
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
        if (!str_contains($content, 'class="box"') && !preg_match('/class="box\s[^"]*"/', $content)) {
            continue;
        }
        if (str_contains($content, 'admin-card')) {
            continue;
        }

        $original = $content;

        $content = preg_replace_callback('/<div class="box(\s[^">]*)?">/', function ($m) {
            if (str_contains($m[0], 'admin-card')) {
                return $m[0];
            }
            return str_replace('class="box', 'class="box admin-card', $m[0]);
        }, $content);

        $content = str_replace('box-header with-border', 'box-header with-border admin-card__header', $content);
        $content = preg_replace('/<h3 class="box-title([^"]*)">/', '<h3 class="box-title admin-card__title$1">', $content);
        $content = preg_replace('/class="box-body(?! admin-card__body)([^"]*)"/', 'class="box-body admin-card__body$1"', $content);
        $content = str_replace('box-tools pull-right', 'box-tools pull-right admin-card__actions', $content);
        $content = preg_replace('/class="table table-hover(?! admin-table)/', 'class="table table-hover admin-table', $content);
        $content = preg_replace('/class="table table-bordered(?! admin-table)/', 'class="table table-bordered admin-table', $content);

        if ($content !== $original) {
            file_put_contents($path, $content);
            $fixed++;
            echo 'OK  ' . str_replace($basePath . DIRECTORY_SEPARATOR, '', $path) . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Fixed {$fixed} form/box partials." . PHP_EOL;
