<?php
/**
 * Batch migrate admin Blade views to modern UI structure.
 * Run from Cafrepay dir: php scripts/migrate_admin_views.php
 */

$basePath = dirname(__DIR__);
$dirs = [
    $basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin',
    $basePath . DIRECTORY_SEPARATOR . 'packages',
];

$iconMap = [
    'order' => 'fa-shopping-cart', 'product' => 'fa-cube', 'inventory' => 'fa-cubes',
    'customer' => 'fa-users', 'merchant' => 'fa-briefcase', 'shop' => 'fa-store',
    'user' => 'fa-user', 'category' => 'fa-tags', 'coupon' => 'fa-ticket',
    'banner' => 'fa-image', 'blog' => 'fa-newspaper-o', 'report' => 'fa-bar-chart',
    'config' => 'fa-cog', 'tax' => 'fa-percent', 'currency' => 'fa-money',
    'country' => 'fa-globe', 'language' => 'fa-language', 'warehouse' => 'fa-building',
    'supplier' => 'fa-truck', 'carrier' => 'fa-ship', 'ticket' => 'fa-life-ring',
    'message' => 'fa-envelope', 'dispute' => 'fa-gavel', 'refund' => 'fa-undo',
    'cart' => 'fa-shopping-basket', 'deliveryboy' => 'fa-motorcycle',
    'subscription' => 'fa-credit-card', 'role' => 'fa-shield', 'faq' => 'fa-question-circle',
    'page' => 'fa-file-text', 'email' => 'fa-envelope-o', 'notification' => 'fa-bell',
    'manufacturer' => 'fa-industry', 'attribute' => 'fa-list', 'slider' => 'fa-sliders',
    'theme' => 'fa-paint-brush', 'package' => 'fa-plug', 'gift-card' => 'fa-gift',
    'account' => 'fa-user-circle', 'dashboard' => 'fa-dashboard', 'promotion' => 'fa-bullhorn',
    'shipping' => 'fa-truck', 'flashdeal' => 'fa-bolt', 'cart' => 'fa-shopping-basket',
    'push_campaign' => 'fa-bullhorn', 'delivery' => 'fa-motorcycle',
];

function guessIcon(string $path, array $map): string
{
    $lower = strtolower(str_replace('\\', '/', $path));
    foreach ($map as $key => $icon) {
        if (str_contains($lower, $key)) {
            return $icon;
        }
    }
    return 'fa-folder-open-o';
}

function migrateFile(string $file, string $viewsRoot, array $iconMap): array
{
    $rel = str_replace($viewsRoot . DIRECTORY_SEPARATOR, '', $file);
    $rel = str_replace('\\', '/', $rel);
    $content = file_get_contents($file);
    $original = $content;
    $changes = [];

    if (!preg_match('/@extends\s*\(\s*[\'"]admin\.layouts\.master[\'"]\s*\)/', $content)) {
        return ['file' => $rel, 'status' => 'skipped', 'reason' => 'not master layout'];
    }

    if (str_contains($content, 'partials.ui.card_start') && str_contains($content, "@section('page_title')")) {
        return ['file' => $rel, 'status' => 'skipped', 'reason' => 'already migrated'];
    }

    // --- Universal: admin-card on boxes (word boundary — avoid box-header/box-body) ---
    if (preg_match('/<div class="box(\s[^">]*)?">/', $content) && preg_match('/<div class="box(?! admin-card)(\s|">)/', $content)) {
        $content = preg_replace('/<div class="box collapsed-box">/', '<div class="box admin-card admin-card--trash collapsed-box">', $content);
        $content = preg_replace_callback('/<div class="box(\s[^">]*)?">/', function ($m) {
            if (str_contains($m[0], 'admin-card')) {
                return $m[0];
            }
            return str_replace('class="box', 'class="box admin-card', $m[0]);
        }, $content);
        $changes[] = 'admin-card';
    }

    // --- Universal: admin-table ---
    $newContent = preg_replace(
        '/class="table table-hover(?! admin-table)/',
        'class="table table-hover admin-table',
        $content
    );
    $newContent = preg_replace(
        '/class="table table-bordered(?! admin-table)/',
        'class="table table-bordered admin-table',
        $newContent
    );
    $newContent = preg_replace(
        '/class="table table-striped(?! admin-table)/',
        'class="table table-striped admin-table',
        $newContent
    );
    if ($newContent !== $content) {
        $content = $newContent;
        $changes[] = 'admin-table';
    }

    // --- Universal: admin-card header/body classes on existing structure ---
    $content = str_replace('box-header with-border', 'box-header with-border admin-card__header', $content);
    $content = str_replace('box-tools pull-right', 'box-tools pull-right admin-card__actions', $content);
    $content = preg_replace('/<h3 class="box-title([^"]*)">/', '<h3 class="box-title admin-card__title$1">', $content);
    $content = preg_replace('/<h1 class="box-title([^"]*)">/', '<h1 class="box-title admin-card__title$1">', $content);
    $content = str_replace('class="box-body responsive-table"', 'class="box-body admin-card__body responsive-table"', $content);
    $content = preg_replace('/class="box-body(?! admin-card__body)([^"]*)"/', 'class="box-body admin-card__body$1"', $content);

    // --- Row actions ---
    $content = preg_replace('/class="row-options(?! admin-row-actions)/', 'class="row-options admin-row-actions', $content);

    // --- Option column header ---
    $content = str_replace('<th>{{ trans(\'app.option\') }}</th>', '<th class="admin-table__actions-col">{{ trans(\'app.option\') }}</th>', $content);

    // --- page_title section ---
    if (!str_contains($content, "@section('page_title')")) {
        if (preg_match('/<h[13][^>]*class="[^"]*admin-card__title[^"]*"[^>]*>\s*(.*?)\s*<\/h[13]>/s', $content, $m)) {
            $titleInner = trim($m[1]);
            if (
                $titleInner
                && !str_contains($titleInner, 'Form::')
                && !str_contains($titleInner, 'Form::button')
                && !preg_match('/trans\s*\(\s*[\'"]app\.trash[\'"]\s*\)/', $titleInner)
                && !str_contains($titleInner, '<')
                && strlen($titleInner) < 120
            ) {
                $insert = "@section('page_title')\n  {$titleInner}\n@endsection\n\n";
                $content = preg_replace(
                    '/(@extends\s*\(\s*[\'"]admin\.layouts\.master[\'"]\s*\)\s*\n)/',
                    "$1{$insert}",
                    $content,
                    1
                );
                $changes[] = 'page_title';
            }
        }
    }

    // --- Add icon wrap to first admin-card__title if plain text trans ---
    if (!str_contains($content, 'admin-card__icon-wrap') && preg_match('/admin-card__title/', $content)) {
        $icon = guessIcon($file, $iconMap);
        $content = preg_replace(
            '/(<h[13] class="box-title admin-card__title[^"]*">)\s*/',
            '$1<span class="admin-card__icon-wrap"><i class="fa ' . $icon . '"></i></span> ',
            $content,
            1
        );
        $changes[] = 'icon';
    }

    // --- Upgrade simple index pages to card_start partial (no actions in header) ---
    $isIndex = str_ends_with($rel, 'index.blade.php') || str_ends_with($rel, 'cancellations.blade.php') || str_ends_with($rel, 'approvals.blade.php');
    if ($isIndex && !str_contains($content, 'partials.ui.card_start') && !str_contains($content, 'box-tools pull-right')) {
        if (preg_match(
            '/<div class="box admin-card">\s*<div class="box-header with-border admin-card__header">\s*<h3 class="box-title admin-card__title[^"]*">\s*<span class="admin-card__icon-wrap[^"]*"><i class="fa ([^"]+)"><\/i><\/span>\s*(.*?)\s*<\/h3>\s*<\/div>\s*(?:<!--[^>]*-->\s*)?<div class="box-body admin-card__body([^"]*)">/s',
            $content,
            $m
        )) {
            $icon = $m[1];
            $titleExpr = trim($m[2]);
            $bodyClass = trim($m[3]);
            $cardStart = "@include('admin.partials.ui.card_start', [\n    'title' => {$titleExpr},\n    'icon' => '{$icon}',\n";
            if ($bodyClass) {
                $cardStart .= "    'bodyClass' => '" . trim($bodyClass) . "',\n";
            }
            $cardStart .= "  ])\n";
            $content = str_replace($m[0], $cardStart, $content);
            $changes[] = 'card_start';

            // card_end before second box or end of section
            if (!str_contains($content, 'partials.ui.card_end')) {
                $content = preg_replace(
                    '/(\s*)<\/div>\s*<!--\s*\/\.box-body\s*-->\s*<\/div>\s*<!--\s*\/\.box\s*-->/',
                    "\n\n  @include('admin.partials.ui.card_end')",
                    $content,
                    1
                );
                if (str_contains($content, 'partials.ui.card_end')) {
                    $changes[] = 'card_end';
                }
            }
        }
    }

    // --- Trash sections ---
    if (str_contains($content, 'admin-card--trash') && !str_contains($content, 'partials.ui.trash_start')) {
        if (preg_match(
            '/<div class="box admin-card admin-card--trash collapsed-box">[\s\S]*?<div class="box-body admin-card__body([^"]*)">/',
            $content,
            $tm
        )) {
            $content = preg_replace(
                '/<div class="box admin-card admin-card--trash collapsed-box">[\s\S]*?<div class="box-body admin-card__body[^"]*">/',
                "@include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])\n",
                $content,
                1
            );
            $changes[] = 'trash_start';
        }
    }

    // Close trash with card_end if trash_start added
    if (str_contains($content, 'partials.ui.trash_start')) {
        // Replace last box closing after trash table
        $content = preg_replace(
            '/(<\/table>\s*)<\/div>\s*<!--\s*\/\.box-body\s*-->\s*<\/div>\s*<!--\s*\/\.box\s*-->\s*(@endsection)/',
            "$1\n  @include('admin.partials.ui.card_end')\n$2",
            $content,
            1
        );
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        return ['file' => $rel, 'status' => 'migrated', 'changes' => array_unique($changes)];
    }

    return ['file' => $rel, 'status' => 'unchanged'];
}

$results = ['migrated' => 0, 'skipped' => 0, 'unchanged' => 0, 'errors' => []];

foreach ($dirs as $viewsRoot) {
    if (!is_dir($viewsRoot)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewsRoot, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!str_ends_with($fileInfo->getFilename(), '.blade.php')) {
            continue;
        }
        $path = $fileInfo->getPathname();
        if (str_contains($path, 'partials' . DIRECTORY_SEPARATOR . 'ui') || str_contains($path, 'partials/ui')) {
            continue;
        }
        if (str_contains($path, 'layouts' . DIRECTORY_SEPARATOR . 'master') || str_contains($path, 'header.blade.php') || str_contains($path, 'sidebar.blade.php') || str_contains($path, 'footer.blade.php')) {
            continue;
        }

        try {
            $result = migrateFile($path, $viewsRoot, $iconMap);
            if ($result['status'] === 'migrated') {
                $results['migrated']++;
                echo 'OK  ' . $result['file'] . ' [' . implode(', ', $result['changes']) . ']' . PHP_EOL;
            } elseif ($result['status'] === 'skipped') {
                $results['skipped']++;
            } else {
                $results['unchanged']++;
            }
        } catch (Throwable $e) {
            $results['errors'][] = $path . ': ' . $e->getMessage();
        }
    }
}

echo PHP_EOL . '=== Migration Summary ===' . PHP_EOL;
echo 'Migrated:  ' . $results['migrated'] . PHP_EOL;
echo 'Skipped:   ' . $results['skipped'] . PHP_EOL;
echo 'Unchanged: ' . $results['unchanged'] . PHP_EOL;
echo 'Errors:    ' . count($results['errors']) . PHP_EOL;
