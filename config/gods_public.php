<?php

$appUrl = (string) env('APP_URL', 'http://localhost');
$appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';

$godsCanonical = env('GODS_CANONICAL_DOMAIN') ?: "tanri.{$appHost}";
$godsAlias = env('GODS_ALIAS_DOMAIN') ?: "gods.{$appHost}";

$godsAliasDomains = array_values(array_unique(array_filter([
    $godsAlias,
    "god.{$appHost}",
    ...array_map('trim', explode(',', (string) env('GODS_EXTRA_ALIAS_DOMAINS', ''))),
], static fn (string $host): bool => $host !== '' && $host !== $godsCanonical)));

return [
    /*
    | Kanonik: tanri.{domain} — gods.{domain} ve god.{domain} aynı pantheona 301 ile yönlendirilir.
    | Ana domain /gods-pantheon yolu da çalışmaya devam eder.
    */
    'domain' => $godsCanonical,
    'alias_domains' => $godsAliasDomains,
    'domains' => array_values(array_unique(array_filter([$godsCanonical, ...$godsAliasDomains]))),

    'title' => 'Tanrılar',
    'motto' => 'Çağlayan inanç, unvanlar ve kutsal alanlar.',
    'tagline' => 'Valiant Shade inanç rehberi — okuyun, düzeltme önerin, yeni tanrı kaydı gönderin.',
    'crest' => 'static/valiant-shade/assets/img/news/guild_celestial_sanctuary.jpg',
];