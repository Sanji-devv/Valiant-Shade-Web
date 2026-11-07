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
    | Varsayılan haber görselleri — gazetede basılmaz; yalnızca özel yüklenen görseller çıkar.
    */
    'default_news_images' => [
        'static/valiant-shade/assets/img/news/guild_arena_tournament.jpg',
        'static/valiant-shade/assets/img/news/guild_arcane_academy.jpg',
        'static/valiant-shade/assets/img/news/guild_celestial_sanctuary.jpg',
        'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
        'static/valiant-shade/assets/img/news/guild_dungeon_crypt.jpg',
        'static/valiant-shade/assets/img/news/guild_fallen_memorial.jpg',
        'static/valiant-shade/assets/img/news/guild_forge_armory.jpg',
        'static/valiant-shade/assets/img/news/guild_hall_council.jpg',
        'static/valiant-shade/assets/img/news/guild_heroes_funeral.jpg',
        'static/valiant-shade/assets/img/news/guild_mystic_forest.jpg',
        'static/valiant-shade/assets/img/news/guild_port_expedition.jpg',
        'static/valiant-shade/assets/img/news/guild_shadow_conspiracy.jpg',
        'static/valiant-shade/assets/img/news/guild_shadow_realm.jpg',
        'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
        'static/valiant-shade/assets/img/news/guild_treasury_vault.jpg',
    ],
];
