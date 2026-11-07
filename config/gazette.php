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

    'title' => 'Lağımdan Sesler',
    'motto' => 'Dedikodu burada doğrulanır — ya da en azından mürekkeple basılır.',
    'tagline' => 'Kraliyet ve lonca dedikoduları, han fısıltıları ve taşra ihbarları — giriş yapmadan okuyun.',
    'press_name' => 'Lağımdan Sesler Basın Bürosu',
    'crest' => 'static/valiant-shade/assets/img/gazette/lagimdan-sesler-crest.jpg',

    /*
    | Evren takvimi — gazete sayı tarihi (gerçek dünya tarihi kullanılmaz).
    */
    'issue' => [
        'number' => 1,
        'label' => 'İlk sayı',
        'year' => 631,
        'month' => 'Leos',
        'weekday' => 'Vonyas',
        'day' => 1,
    ],

    /*
    | Woodcut illüstrasyonlar — gazete / PDF görünümünde haber türüne göre.
    */
    'illustrations' => [
        'royal_news' => 'static/valiant-shade/assets/img/gazette/lagim_royal_hall.jpg',
        'guild_news' => 'static/valiant-shade/assets/img/gazette/lagim_treasury.jpg',
        'royal_rumor' => 'static/valiant-shade/assets/img/gazette/lagim_royal_hall.jpg',
        'guild_rumor' => 'static/valiant-shade/assets/img/gazette/lagim_whispers.jpg',
        'environment_rumor' => 'static/valiant-shade/assets/img/gazette/lagim_forest_rumor.jpg',
        'local_news' => 'static/valiant-shade/assets/img/gazette/lagim_local_market.jpg',
        'default' => 'static/valiant-shade/assets/img/gazette/lagim_whispers.jpg',
    ],

    'illustration_variants' => [
        'static/valiant-shade/assets/img/gazette/lagim_whispers.jpg',
        'static/valiant-shade/assets/img/gazette/lagim_harbor_dragon.jpg',
        'static/valiant-shade/assets/img/gazette/lagim_academy.jpg',
        'static/valiant-shade/assets/img/gazette/lagim_treasury.jpg',
        'static/valiant-shade/assets/img/gazette/lagim_forest_rumor.jpg',
        'static/valiant-shade/assets/img/gazette/lagim_local_market.jpg',
        'static/valiant-shade/assets/img/gazette/lagim_royal_hall.jpg',
    ],
];
