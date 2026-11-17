<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Halka açık site (ana domain + gazette) — arama ve paylaşım meta verileri
    |--------------------------------------------------------------------------
    |
    | APP_NAME depo/ad içindir; Google başlığı için site_name kullanın.
    |
    */
    'site_name' => env('SEO_SITE_NAME', 'Valiant Shade'),

    'home' => [
        'title' => env('SEO_HOME_TITLE', 'Valiant Shade | Pathfinder lonca portali'),
        'description' => env(
            'SEO_HOME_DESCRIPTION',
            'Pathfinder tabanlı Valiant Shade loncasının resmi sitesi: Lağımdan Sesler gazetesi, canavar veritabanı, interaktif dünya haritası ve lonca kayıtları.',
        ),
        'keywords' => env(
            'SEO_HOME_KEYWORDS',
            'Valiant Shade, Pathfinder, lonca, Lağımdan Sesler, gazete, canavar veritabanı, Mineria',
        ),
        'og_image' => env(
            'SEO_HOME_OG_IMAGE',
            'static/valiant-shade/assets/img/branding/valiant-shade-logo-small.png',
        ),
    ],

    'gazette' => [
        'title_suffix' => env('SEO_GAZETTE_TITLE_SUFFIX', 'Valiant Shade'),
    ],

    'favicon_png' => 'static/valiant-shade/assets/img/favicon/favicon.png',
    'apple_touch_icon' => 'static/valiant-shade/assets/img/branding/valiant-shade-logo-small.png',

    /*
    |--------------------------------------------------------------------------
    | llms.txt — yapay zeka ajanları için halka açık site özeti
    |--------------------------------------------------------------------------
    |
    | /llms.txt (kısa) ve /llms-full.txt (haber listesi ile genişletilmiş)
    |
    */
    'llms' => [
        'contact' => env('SEO_LLMS_CONTACT', ''),
        'full_article_limit' => (int) env('SEO_LLMS_FULL_ARTICLE_LIMIT', 40),
    ],
];
