<?php

/**
 * Dünya haritası konum hiyerarşisi (parent → child).
 * Sidebar ve mobil menüde breadcrumb-style gezinme için.
 */
return [
    'kingdom_order' => ['feldan', 'rovesta'],

    'map_keys' => [
        'feldan' => 'feldan',
        'rovesta' => 'rovesta-kingdom',
        'isonburg' => 'isonburg-kingdom',
    ],

    'locations' => [
        'feldan' => [
            'label' => 'Feldan Krallığı',
            'description' => 'Krallıklar diyarına hoş geldiniz',
            'route' => 'web.feldan',
            'parent' => null,
        ],
        'isonburg' => [
            'label' => 'Isonburg Krallığı',
            'description' => 'Güney kıyısındaki krallık',
            'route' => 'web.isonburg',
            'parent' => 'feldan',
        ],
        'rovesta' => [
            'label' => 'Rovesta Krallığı',
            'description' => 'Doğu diyarının krallığı',
            'route' => 'web.rovesta',
            'parent' => null,
        ],
        'isonburg-city' => [
            'label' => 'Isonburg',
            'description' => 'Krallığın başkenti',
            'route' => 'web.isonburg.city',
            'parent' => 'isonburg',
        ],
        'valiant-shade' => [
            'label' => 'Valiant Shade',
            'description' => 'Lonca arazisi',
            'route' => 'web.valiant-shade',
            'parent' => 'isonburg',
        ],
    ],
];
