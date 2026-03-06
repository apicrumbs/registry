<?php

/**
 * ApiCrumbs Registry Health Checker
 * Pings 250+ API endpoints to detect 404s/500s.
 */

$manifest = json_decode(file_get_contents(__DIR__ . '/../manifest.json'), true);
$results = [];

echo "\e[1;34m📡 Pinging the Registry Ecosystem...\e[0m\n\n";

foreach ($manifest['providers'] as $p) {
    if (empty($p['url'])) continue;

    echo "Checking \e[32m{$p['name']}\e[0m... ";

    $ch = curl_init($p['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
    curl_exec($ch);
    
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 400) {
        echo "\e[32m[ALIVE ({$code})]\e[0m\n";
    } else {
        echo "\e[31m[DOWN ({$code})]\e[0m\n";
        $results[] = "Provider {$p['name']} might be broken (HTTP {$code})";
    }
}

if (!empty($results)) {
    echo "\n\e[31m⚠️  Alert: " . count($results) . " providers need attention.\e[0m\n";
}