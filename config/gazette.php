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

    'schedule' => 'Her Vonyas yeni sayı basılır — arşivden geçmiş yapraklara dönebilirsiniz.',

    /*
    | Gazete görselleri yalnızca woodcut / mürekkep — AI filigranı veya “üretim” etiketi yok.
    | public API etiket ve parıltı ikonlarını göstermez.
    */
    'public_strip_content_tags' => true,

    'inner_articles_per_sheet' => 3,

    'corner_slots' => [
        [
            'key' => 'han_sozu',
            'title' => 'Han Sözü',
            'rumuz' => null,
            'body' => null,
        ],
        [
            'key' => 'simyaci',
            'title' => 'Simyacının Defteri',
            'rumuz' => null,
            'body' => null,
        ],
        [
            'key' => 'bahisci',
            'title' => 'Bahis Köşesi',
            'rumuz' => null,
            'body' => null,
        ],
        [
            'key' => 'kahin',
            'title' => 'Kahinin Fısıltısı',
            'rumuz' => null,
            'body' => null,
        ],
    ],

    /*
    | Varsayılan haber görselleri — gazetede basılmaz; yalnızca özel yüklenen görseller çıkar.
    */
    /*
    | Başlık parçası => özel woodcut (gazetede yalnızca bunlar basılır).
    */
    'title_image_map' => [
        'Caspian' => 'static/valiant-shade/assets/img/gazette/news/caspian_tavern.jpg',
        'Yusari' => 'static/valiant-shade/assets/img/gazette/news/yusari_gods.jpg',
        'şövalye' => 'static/valiant-shade/assets/img/gazette/news/knight_economy.jpg',
        'şovalye' => 'static/valiant-shade/assets/img/gazette/news/knight_economy.jpg',
        'Mirang' => 'static/valiant-shade/assets/img/gazette/news/mirang_war.jpg',
        'Quinia' => 'static/valiant-shade/assets/img/gazette/news/mirang_war.jpg',
        'Temran' => 'static/valiant-shade/assets/img/gazette/news/tavern_gossip.jpg',
        'Bailiff' => 'static/valiant-shade/assets/img/gazette/news/guild_council.jpg',
        'Doran' => 'static/valiant-shade/assets/img/gazette/news/guild_council.jpg',
        'Söylenti' => 'static/valiant-shade/assets/img/gazette/news/tavern_gossip.jpg',
        'Lonca' => 'static/valiant-shade/assets/img/gazette/news/guild_council.jpg',
        'lonca' => 'static/valiant-shade/assets/img/gazette/news/guild_council.jpg',
    ],

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
