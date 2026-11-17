<?php

$appUrl = (string) env('APP_URL', 'http://localhost');
$appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';

$gazetteCanonical = env('GAZETTE_CANONICAL_DOMAIN') ?: "gazete.{$appHost}";
$gazetteAlias = env('GAZETTE_ALIAS_DOMAIN') ?: 'gazette.'.$appHost;

$gazetteAliasDomains = $gazetteAlias !== $gazetteCanonical
    ? array_values(array_unique([$gazetteAlias]))
    : [];

return [
    /*
    |--------------------------------------------------------------------------
    | Gazete (halka açık ön yüz) alt alan adı
    |--------------------------------------------------------------------------
    |
    | Kanonik: gazete.{domain} — gazette.{domain} aynı içeriğe 301 ile yönlendirilir.
    |
    */
    'domain' => $gazetteCanonical,
    'alias_domains' => $gazetteAliasDomains,
    'domains' => array_values(array_unique(array_filter([$gazetteCanonical, ...$gazetteAliasDomains]))),

    'title' => 'Lağımdan Sesler',
    'motto' => 'Dedikodu burada doğrulanır; yoksa da en azından mürekkeple basılır.',
    'tagline' => 'Kraliyet ve lonca dedikoduları, han fısıltıları ve taşra ihbarları. Giriş yapmadan okuyun.',
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

    'schedule' => 'Her Vonyas yeni sayı basılır. Arşivden geçmiş yapraklara dönebilirsiniz.',

    /*
    | Gazete görselleri yalnızca woodcut / mürekkep — AI filigranı veya “üretim” etiketi yok.
    | public API etiket ve parıltı ikonlarını göstermez.
    */
    'public_strip_content_tags' => true,

    'submission_slots' => [
        'lead' => [
            'modal_title' => 'Manşet gönder',
            'modal_intro' => 'Onaydan sonra yalnızca üst slaytta döner; haber listesine girmez.',
            'title_placeholder' => 'Manşet başlığı…',
            'body_label' => 'Manşet metni',
            'body_placeholder' => 'Kısa ve net manşet metni…',
        ],
        'flash' => [
            'modal_title' => 'Sıcak haber gönder',
            'modal_intro' => 'Onaydan sonra günlük ek sayı olarak basılır (ör. 3.2).',
        ],
        'corner' => [
            'modal_title' => 'Köşe yazısı gönder',
            'modal_intro' => 'Onaydan sonra seçtiğiniz köşe sütununda basılır; aynı köşeye birden fazla yazı eklenebilir.',
            'title_placeholder' => 'Köşe başlığı (isteğe bağlı)…',
            'body_label' => 'Köşe metni',
            'body_placeholder' => 'Kısa köşe yazısı. Sesinizi koruyun, abartmayın…',
            'show_rumuz' => true,
        ],
        'general' => [
            'modal_title' => 'Haftalık haber gönder',
            'modal_intro' => 'Seçtiğiniz sayıya işlenen tam haber. Editör onayı gerekir.',
            'title_placeholder' => 'Örn. Kızıl Han\'da gizli buluşma…',
            'body_label' => 'Haber metni',
            'body_placeholder' => 'Gördüklerinizi veya duyduklarınızı yazın…',
        ],
    ],

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
            'title' => 'Maske Düştü',
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
