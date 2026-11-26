<?php

/**
 * NPC yaşam / varlık durumu (veritabanında unsignedTinyInteger).
 * Etiketler: lang dosyalarında npc_status.{key}
 */
return [
    'default' => 5,
    'options' => [
        1 => ['key' => 'alive', 'badge' => 'success'],
        2 => ['key' => 'deceased', 'badge' => 'danger'],
        3 => ['key' => 'missing', 'badge' => 'warning'],
        4 => ['key' => 'captive', 'badge' => 'danger'],
        5 => ['key' => 'unknown', 'badge' => 'secondary'],
        6 => ['key' => 'imprisoned', 'badge' => 'danger'],
        7 => ['key' => 'exiled', 'badge' => 'warning'],
        8 => ['key' => 'fugitive', 'badge' => 'info'],
        9 => ['key' => 'retired', 'badge' => 'primary'],
    ],
];