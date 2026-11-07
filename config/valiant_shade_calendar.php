<?php

/*
|--------------------------------------------------------------------------
| Valiant Shade evren takvimi
|--------------------------------------------------------------------------
|
| Gün ve ay adları: yerel (TR) ↔ evren (in_world).
| Yıl: gregorian + year_offset → evren yılı (ör. 2026 → 631).
|
*/

return [
    'year_offset' => 631 - 2026,

    'weekdays' => [
        0 => ['in_world' => 'Vonyas', 'terrestrial' => 'Pazar'],
        1 => ['in_world' => 'Vartre', 'terrestrial' => 'Pazartesi'],
        2 => ['in_world' => 'Biniru', 'terrestrial' => 'Salı'],
        3 => ['in_world' => 'Dustar', 'terrestrial' => 'Çarşamba'],
        4 => ['in_world' => 'Sherry', 'terrestrial' => 'Perşembe'],
        5 => ['in_world' => 'Honza', 'terrestrial' => 'Cuma'],
        6 => ['in_world' => 'Saemon', 'terrestrial' => 'Cumartesi'],
    ],

    'months' => [
        1 => ['in_world' => 'Silvar', 'terrestrial' => 'Ocak'],
        2 => ['in_world' => 'Ren', 'terrestrial' => 'Şubat'],
        3 => ['in_world' => 'Hoolin', 'terrestrial' => 'Mart'],
        4 => ['in_world' => 'Kean', 'terrestrial' => 'Nisan'],
        5 => ['in_world' => 'Vakhan', 'terrestrial' => 'Mayıs'],
        6 => ['in_world' => 'Balan', 'terrestrial' => 'Haziran'],
        7 => ['in_world' => 'Belaran', 'terrestrial' => 'Temmuz'],
        8 => ['in_world' => 'Shean', 'terrestrial' => 'Ağustos'],
        9 => ['in_world' => 'Roran', 'terrestrial' => 'Eylül'],
        10 => ['in_world' => 'Siwan', 'terrestrial' => 'Ekim'],
        11 => ['in_world' => 'Leos', 'terrestrial' => 'Kasım'],
        12 => ['in_world' => 'Dosan', 'terrestrial' => 'Aralık'],
    ],
];
