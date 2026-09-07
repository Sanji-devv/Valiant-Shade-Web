<?php

/**
 * Dünya haritası konum hiyerarşisi (parent → child).
 * Sidebar ve mobil menüde breadcrumb-style gezinme için.
 */
return [
    'locations' => [
        'feldan' => [
            'label' => 'Feldan Krallığı',
            'description' => 'Krallıklar diyarına hoş geldiniz',
            'route' => 'web.feldan',
            'parent' => null,
        ],
        'isonburg' => [
            'label' => 'Isonburg Krallığı',
            'description' => 'Bölge haritası',
            'route' => 'web.isonburg',
            'parent' => 'feldan',
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
