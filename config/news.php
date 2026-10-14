<?php

return [
    'public_cache_ttl' => (int) env('NEWS_PUBLIC_CACHE_TTL', 300),

    'types' => [
        'royal_news' => [
            'label' => 'Kraliyet Haberi',
            'picker_label' => 'Kraliyet',
            'badge_color' => 'primary',
            'category_icon' => 'icon-base ti tabler-crown',
            'filter_class' => 'filter-royal',
        ],
        'guild_news' => [
            'label' => 'Lonca Haberi',
            'picker_label' => 'Lonca',
            'badge_color' => 'success',
            'category_icon' => 'icon-base ti tabler-shield',
            'filter_class' => 'filter-guild',
        ],
        'royal_rumor' => [
            'label' => 'Kraliyet Söylentisi',
            'picker_label' => 'Kral. Söylenti',
            'badge_color' => 'info',
            'category_icon' => 'icon-base ti tabler-building-castle',
            'filter_class' => 'filter-rumor',
        ],
        'guild_rumor' => [
            'label' => 'Lonca Söylentisi',
            'picker_label' => 'Lonca Söylenti',
            'badge_color' => 'warning',
            'category_icon' => 'icon-base ti tabler-beer',
            'filter_class' => 'filter-rumor',
        ],
        'environment_rumor' => [
            'label' => 'Çevre Söylentileri',
            'picker_label' => 'Çevre',
            'badge_color' => 'secondary',
            'category_icon' => 'icon-base ti tabler-trees',
            'filter_class' => 'filter-env',
        ],
        'local_news' => [
            'label' => 'Yerel Haberler',
            'picker_label' => 'Yerel',
            'badge_color' => 'dark',
            'category_icon' => 'icon-base ti tabler-map-pin',
            'filter_class' => 'filter-local',
        ],
    ],

    'statuses' => [
        'pending' => 'Beklemede',
        'published' => 'Yayında',
        'rejected' => 'Reddedildi',
    ],
];
