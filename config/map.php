<?php

$appUrl = (string) env('APP_URL', 'http://localhost');
$appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';

$mapCanonical = env('MAP_CANONICAL_DOMAIN') ?: "harita.{$appHost}";
$mapAlias = env('MAP_ALIAS_DOMAIN') ?: "map.{$appHost}";

$mapAliasDomains = $mapAlias !== $mapCanonical
    ? array_values(array_unique([$mapAlias]))
    : [];

return [
    /*
    |--------------------------------------------------------------------------
    | Dünya haritası (halka açık ön yüz) alt alan adı
    |--------------------------------------------------------------------------
    |
    | Kanonik: harita.{domain} — map.{domain} aynı içeriğe 301 ile yönlendirilir.
    |
    */
    'domain' => $mapCanonical,
    'alias_domains' => $mapAliasDomains,
    'domains' => array_values(array_unique(array_filter([$mapCanonical, ...$mapAliasDomains]))),
];
