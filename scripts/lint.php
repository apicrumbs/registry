<?php

/**
 * ApiCrumbs Registry Linter
 * Ensures all 250+ providers are production-ready.
 */

require __DIR__ . '/../vendor/autoload.php';

use ApiCrumbs\Core\Contracts\ProviderInterface;

$registryPath = realpath(__DIR__ . '/../src/Providers');
$manifestPath = __DIR__ . '/../manifest.json';
$errors = [];

echo "\e[1;34m🔍 Starting ApiCrumbs Registry Lint...\e[0m\n\n";

// 1. Validate Manifest JSON
if (!file_exists($manifestPath)) {
    die("\e[31m❌ Error: manifest.json missing!\e[0m\n");
}
$manifest = json_decode(file_get_contents($manifestPath), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("\e[31m❌ Error: manifest.json is invalid JSON!\e[0m\n");
}

$manifestIds = array_column($manifest['providers'], 'id');

// 2. Scan Provider Files
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($registryPath));

foreach ($files as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;

    $filePath = $file->getRealPath();
    $relativePath = str_replace($registryPath . DIRECTORY_SEPARATOR, '', $filePath);
    $providerId = strtolower(str_replace(['Provider.php', DIRECTORY_SEPARATOR], ['', '/'], $relativePath));

    echo "Checking: \e[32m{$providerId}\e[0m... ";

    // A. Syntax Check (Lint)
    exec("php -l " . escapeshellarg($filePath), $output, $returnVar);
    if ($returnVar !== 0) {
        echo "\e[31m[SYNTAX ERROR]\e[0m\n";
        $errors[] = "Syntax error in {$relativePath}";
        continue;
    }

    // B. Interface & Naming Check
    // We use reflection or simple token parsing to avoid executing untrusted code
    $content = file_get_contents($filePath);
    
    if (strpos($content, 'implements ProviderInterface') === false) {
        echo "\e[31m[MISSING INTERFACE]\e[0m\n";
        $errors[] = "{$relativePath} does not implement ProviderInterface";
        continue;
    }

    // C. Manifest Alignment
    if (!in_array($providerId, $manifestIds)) {
        echo "\e[33m[NOT IN MANIFEST]\e[0m\n";
        $errors[] = "Provider '{$providerId}' exists but is missing from manifest.json";
        continue;
    }

    echo "\e[32m[OK]\e[0m\n";
}

// 3. Summary
echo "\n" . str_repeat("-", 40) . "\n";
if (empty($errors)) {
    echo "\e[1;32m✅ Lint Passed! All providers are valid.\e[0m\n";
    exit(0);
} else {
    echo "\e[1;31m❌ Lint Failed with " . count($errors) . " errors:\e[0m\n";
    foreach ($errors as $error) {
        echo " - {$error}\n";
    }
    exit(1);
}