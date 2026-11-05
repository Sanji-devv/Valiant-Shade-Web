<?php

$appUrl = (string) env('APP_URL', 'http://localhost');
$appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
$subdomain = (string) env('MAP_SUBDOMAIN', 'map');

return [
    /*
    |--------------------------------------------------------------------------
    | Dünya haritası (halka açık ön yüz) alt alan adı
    |--------------------------------------------------------------------------
    |
    | Örnek: map.valiantshade.com/feldan — ziyaretçi harita sayfaları.
    | Harita düzenleme (/…/edit) ve editör API ana domain üzerinde kalır.
    |
    */
    'domain' => env('MAP_DOMAIN') ?: "{$subdomain}.{$appHost}",
];
