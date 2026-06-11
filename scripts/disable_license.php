<?php

/**
 * Disables Incevio APL license verification by replacing license gate functions
 * in vendor/helper_functions.php with no-op stubs that always report success.
 */

$file = dirname(__DIR__).'/vendor/helper_functions.php';

if (! is_file($file)) {
    fwrite(STDERR, "helper_functions.php not found\n");
    exit(1);
}

$content = file_get_contents($file);

$stub = <<<'PHP'
return [
    'notification_case' => 'notification_license_ok',
    'notification_text' => '',
];
PHP;

$functionNames = [
    'incevioAutoloadHelpers',
    'aplVerifySupport',
    'aplVerifyUpdates',
    'incevioVerify',
    'incevioUpdateLicense',
    'incevioUninstallLicense',
    'incevioVerifyLicense',
    'preparePackageInstallation',
    'prepareThemeInstallation',
];

foreach ($functionNames as $name) {
    if (! preg_match('/function\s+'.preg_quote($name, '/').'\s*\([^)]*\)\s*\{/', $content, $match, PREG_OFFSET_CAPTURE)) {
        echo "Skip (not found): {$name}\n";
        continue;
    }

    $start = $match[0][1];
    $brace = strpos($content, '{', $start);
    if ($brace === false) {
        fwrite(STDERR, "Could not find opening brace for {$name}\n");
        exit(1);
    }

    $depth = 0;
    $end = null;
    $len = strlen($content);
    for ($i = $brace; $i < $len; $i++) {
        if ($content[$i] === '{') {
            $depth++;
        } elseif ($content[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                $end = $i;
                break;
            }
        }
    }

    if ($end === null) {
        fwrite(STDERR, "Could not find closing brace for {$name}\n");
        exit(1);
    }

    $signature = substr($content, $start, $brace - $start + 1);
    $replacement = $signature."\n    ".$stub."\n}";
    $content = substr($content, 0, $start).$replacement.substr($content, $end + 1);
    echo "Patched: {$name}\n";
}

file_put_contents($file, $content);
echo "License verification disabled in {$file}\n";
