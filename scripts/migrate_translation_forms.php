<?php
/**
 * Wrap translation form partials with card_start/card_end.
 * Run: php scripts/migrate_translation_forms.php
 */

$base = dirname(__DIR__) . '/resources/views/admin';

$files = glob($base . '/**/*translation_form*.blade.php', GLOB_BRACE);
$files = array_merge($files, glob($base . '/**/_translation_form.blade.php'));

$files = array_unique($files);

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'card_start') !== false) {
        echo "Skip (already migrated): " . basename($file) . "\n";
        continue;
    }

    // Extract title from h3 box-title
    if (!preg_match('/<h3 class="box-title admin-card__title">\s*(.*?)\s*<\/h3>/s', $content, $m)) {
        echo "No title found: {$file}\n";
        continue;
    }
    $titleBlade = trim($m[1]);

    // Extract optional header actions
    $actions = '';
    if (preg_match('/<div class="box-tools pull-right admin-card__actions">\s*(.*?)\s*<\/div>\s*<\/div>\s*\{\{-- box header --\}\}/s', $content, $am)) {
        $actions = trim($am[1]);
    } elseif (preg_match('/<div class="box-header with-border admin-card__header">.*?<div class="box-tools pull-right admin-card__actions">\s*(.*?)\s*<\/div>\s*<\/div>\s*<div class="box-body/s', $content, $am)) {
        $actions = trim($am[1]);
    }

    $actionsParam = $actions ? ",\n  'actions' => '" . addslashes(str_replace(["\r", "\n"], ' ', $actions)) . "'" : '';

    // Remove box wrapper opening
    $content = preg_replace(
        '/<div class="box admin-card">\s*<div class="box-header with-border admin-card__header">\s*<h3 class="box-title admin-card__title">\s*.*?\s*<\/h3>\s*(?:<div class="box-tools pull-right admin-card__actions">.*?<\/div>\s*)?<\/div>\s*(?:\{\{-- box header --\}\}\s*)?<div class="box-body admin-card__body">/s',
        "@include('admin.partials.ui.card_start', [\n  'title' => {$titleBlade},\n  'icon' => 'fa-language',\n  'class' => 'admin-form-section'{$actionsParam}\n])\n",
        $content,
        1,
        $count
    );

    if (!$count) {
        echo "Could not replace opening in: {$file}\n";
        continue;
    }

    // Replace footer submit + closing divs
    $content = preg_replace(
        '/<div class="box-tools pull-right admin-card__actions">\s*<button type="submit" class="btn btn-flat btn-lg btn-primary">(.*?)<\/button>\s*<\/div>\s*<\/div>\s*<\/div>/s',
        "<div class=\"text-right\">\n      <button type=\"submit\" class=\"btn btn-flat btn-lg btn-new\">$1</button>\n    </div>\n@include('admin.partials.ui.card_end')",
        $content,
        1,
        $count2
    );

    if (!$count2) {
        $content = preg_replace(
            '/<div class="box-tools pull-right admin-card__actions">\s*<button type="submit" class="btn btn-flat btn-lg btn-primary">(.*?)<\/button>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/s',
            "<div class=\"text-right\">\n      <button type=\"submit\" class=\"btn btn-flat btn-lg btn-new\">$1</button>\n    </div>\n@include('admin.partials.ui.card_end')\n  </div>\n</div>",
            $content
        );
    }

    file_put_contents($file, $content);
    echo "Migrated: " . str_replace($base . DIRECTORY_SEPARATOR, '', $file) . "\n";
}

echo "Done.\n";
