<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Valiant Shade - Pathfinder 1e Fast XP Progression & Level Failsafe Enum
    |--------------------------------------------------------------------------
    |
    | Master Sheet içerisindeki Level / XP / Failsafe tablosunun birebir enum karşılığıdır.
    | Oyuncuların tecrübe puanları (XP) ve seviye atlamaları bu tablo üzerinden takip edilir.
    |
    */
    'table' => [
        ['level' => 0,  'xp' => 0,       'failsafe' => 0],
        ['level' => 1,  'xp' => 0,       'failsafe' => 1],
        ['level' => 2,  'xp' => 1300,    'failsafe' => 2],
        ['level' => 3,  'xp' => 3300,    'failsafe' => 3],
        ['level' => 4,  'xp' => 6000,    'failsafe' => 4],
        ['level' => 5,  'xp' => 10000,   'failsafe' => 5],
        ['level' => 6,  'xp' => 15000,   'failsafe' => 6],
        ['level' => 7,  'xp' => 23000,   'failsafe' => 7],
        ['level' => 8,  'xp' => 34000,   'failsafe' => 8],
        ['level' => 9,  'xp' => 50000,   'failsafe' => 9],
        ['level' => 10, 'xp' => 71000,   'failsafe' => 10],
        ['level' => 11, 'xp' => 105000,  'failsafe' => 11],
        ['level' => 12, 'xp' => 145000,  'failsafe' => 12],
        ['level' => 13, 'xp' => 210000,  'failsafe' => 13],
        ['level' => 14, 'xp' => 295000,  'failsafe' => 14],
        ['level' => 15, 'xp' => 425000,  'failsafe' => 15],
        ['level' => 16, 'xp' => 600000,  'failsafe' => 16],
        ['level' => 17, 'xp' => 850000,  'failsafe' => 17],
        ['level' => 18, 'xp' => 1200000, 'failsafe' => 18],
        ['level' => 19, 'xp' => 1700000, 'failsafe' => 19],
        ['level' => 20, 'xp' => 2400000, 'failsafe' => 20],
    ],

    /*
    |--------------------------------------------------------------------------
    | Level => Minimum Required XP Map
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        0  => 0,
        1  => 0,
        2  => 1300,
        3  => 3300,
        4  => 6000,
        5  => 10000,
        6  => 15000,
        7  => 23000,
        8  => 34000,
        9  => 50000,
        10 => 71000,
        11 => 105000,
        12 => 145000,
        13 => 210000,
        14 => 295000,
        15 => 425000,
        16 => 600000,
        17 => 850000,
        18 => 1200000,
        19 => 1700000,
        20 => 2400000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Failsafe Mapping (Level => Failsafe value)
    |--------------------------------------------------------------------------
    */
    'failsafes' => [
        0  => 0,
        1  => 1,
        2  => 2,
        3  => 3,
        4  => 4,
        5  => 5,
        6  => 6,
        7  => 7,
        8  => 8,
        9  => 9,
        10 => 10,
        11 => 11,
        12 => 12,
        13 => 13,
        14 => 14,
        15 => 15,
        16 => 16,
        17 => 17,
        18 => 18,
        19 => 19,
        20 => 20,
    ],
];