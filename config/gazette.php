<?php

$appUrl = (string) env('APP_URL', 'http://localhost');
$appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
$subdomain = (string) env('GAZETTE_SUBDOMAIN', 'gazette');

return [
    /*
    |--------------------------------------------------------------------------
    | Gazete (halka açık ön yüz) alt alan adı
    |--------------------------------------------------------------------------
    |
    | Örnek: gazette.valiantshade.com — yalnızca müşteri/ziyaretçi gazetesi.
    | Valiant Shade paneli (/valiant-shade/pages/news) ana domain üzerinde kalır.
    |
    */
    'domain' => env('GAZETTE_DOMAIN') ?: "{$subdomain}.{$appHost}",
];
