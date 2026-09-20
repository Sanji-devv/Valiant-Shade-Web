<?php

return [
    'barbarian' => [
        'name'             => 'Barbarian',
        'category'         => 'core',
        'hit_die'          => 'd12',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none',
        'alignment'        => 'Any non-lawful',
    ],

    'bard' => [
        'name'             => 'Bard',
        'category'         => 'core',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 6,
        'spellcasting'     => 'arcane (spontaneous, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'cleric' => [
        'name'             => 'Cleric',
        'category'         => 'core',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 2,
        'spellcasting'     => 'divine (prepared, up to 9th level)',
        'alignment'        => 'Within one step of deity',
    ],

    'druid' => [
        'name'             => 'Druid',
        'category'         => 'core',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'divine (prepared, up to 9th level)',
        'alignment'        => 'Any neutral',
    ],

    'fighter' => [
        'name'             => 'Fighter',
        'category'         => 'core',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 2,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'monk' => [
        'name'             => 'Monk',
        'category'         => 'core',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none (ki powers)',
        'alignment'        => 'Any lawful',
    ],

    'paladin' => [
        'name'             => 'Paladin',
        'category'         => 'core',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 2,
        'spellcasting'     => 'divine (prepared, up to 4th level)',
        'alignment'        => 'Lawful Good',
    ],

    'ranger' => [
        'name'             => 'Ranger',
        'category'         => 'core',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 6,
        'spellcasting'     => 'divine (prepared, up to 4th level)',
        'alignment'        => 'Any',
    ],

    'rogue' => [
        'name'             => 'Rogue',
        'category'         => 'core',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 8,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'sorcerer' => [
        'name'             => 'Sorcerer',
        'category'         => 'core',
        'hit_die'          => 'd6',
        'bab_table'        => 'bab_section_3',
        'skill_ranks'      => 2,
        'spellcasting'     => 'arcane (spontaneous, up to 9th level)',
        'alignment'        => 'Any',
    ],

    'wizard' => [
        'name'             => 'Wizard',
        'category'         => 'core',
        'hit_die'          => 'd6',
        'bab_table'        => 'bab_section_3',
        'skill_ranks'      => 2,
        'spellcasting'     => 'arcane (prepared, up to 9th level)',
        'alignment'        => 'Any',
    ],

    'alchemist' => [
        'name'             => 'Alchemist',
        'category'         => 'base',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'alchemy (extracts, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'cavalier' => [
        'name'             => 'Cavalier',
        'category'         => 'base',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'gunslinger' => [
        'name'             => 'Gunslinger',
        'category'         => 'base',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none (grit deeds)',
        'alignment'        => 'Any',
    ],

    'inquisitor' => [
        'name'             => 'Inquisitor',
        'category'         => 'base',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 6,
        'spellcasting'     => 'divine (spontaneous, up to 6th level)',
        'alignment'        => 'Within one step of deity',
    ],

    'magus' => [
        'name'             => 'Magus',
        'category'         => 'base',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 2,
        'spellcasting'     => 'arcane (prepared, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'omdura' => [
        'name'             => 'Omdura',
        'category'         => 'base',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'divine (prepared, up to 6th level)',
        'alignment'        => 'Within one step of deity',
    ],

    'oracle' => [
        'name'             => 'Oracle',
        'category'         => 'base',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'divine (spontaneous, up to 9th level)',
        'alignment'        => 'Any',
    ],

    'shifter' => [
        'name'             => 'Shifter',
        'category'         => 'base',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none',
        'alignment'        => 'Any neutral',
    ],

    'summoner' => [
        'name'             => 'Summoner',
        'category'         => 'base',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 2,
        'spellcasting'     => 'arcane (spontaneous, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'vampire_hunter' => [
        'name'             => 'Vampire Hunter',
        'category'         => 'base',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 6,
        'spellcasting'     => 'divine (spontaneous, up to 4th level)',
        'alignment'        => 'Any',
    ],

    'vigilante' => [
        'name'             => 'Vigilante',
        'category'         => 'base',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 6,
        'spellcasting'     => 'none (optional archetype casting)',
        'alignment'        => 'Any',
    ],

    'witch' => [
        'name'             => 'Witch',
        'category'         => 'base',
        'hit_die'          => 'd6',
        'bab_table'        => 'bab_section_3',
        'skill_ranks'      => 2,
        'spellcasting'     => 'arcane (prepared, up to 9th level)',
        'alignment'        => 'Any',
    ],

    'arcanist' => [
        'name'             => 'Arcanist',
        'category'         => 'hybrid',
        'parent_classes'   => ['sorcerer', 'wizard'],
        'hit_die'          => 'd6',
        'bab_table'        => 'bab_section_3',
        'skill_ranks'      => 2,
        'spellcasting'     => 'arcane (hybrid prepared/spontaneous, up to 9th level)',
        'alignment'        => 'Any',
    ],

    'bloodrager' => [
        'name'             => 'Bloodrager',
        'category'         => 'hybrid',
        'parent_classes'   => ['barbarian', 'sorcerer'],
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'arcane (spontaneous, up to 4th level)',
        'alignment'        => 'Any non-lawful',
    ],

    'brawler' => [
        'name'             => 'Brawler',
        'category'         => 'hybrid',
        'parent_classes'   => ['fighter', 'monk'],
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'hunter' => [
        'name'             => 'Hunter',
        'category'         => 'hybrid',
        'parent_classes'   => ['druid', 'ranger'],
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 6,
        'spellcasting'     => 'divine (spontaneous, up to 6th level)',
        'alignment'        => 'Any neutral',
    ],

    'investigator' => [
        'name'             => 'Investigator',
        'category'         => 'hybrid',
        'parent_classes'   => ['alchemist', 'rogue'],
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 6,
        'spellcasting'     => 'alchemy (extracts, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'shaman' => [
        'name'             => 'Shaman',
        'category'         => 'hybrid',
        'parent_classes'   => ['oracle', 'witch'],
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'divine (prepared, up to 9th level)',
        'alignment'        => 'Any',
    ],

    'skald' => [
        'name'             => 'Skald',
        'category'         => 'hybrid',
        'parent_classes'   => ['barbarian', 'bard'],
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'arcane (spontaneous, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'slayer' => [
        'name'             => 'Slayer',
        'category'         => 'hybrid',
        'parent_classes'   => ['ranger', 'rogue'],
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 6,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'swashbuckler' => [
        'name'             => 'Swashbuckler',
        'category'         => 'hybrid',
        'parent_classes'   => ['fighter', 'gunslinger'],
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none (panache deeds)',
        'alignment'        => 'Any',
    ],

    'warpriest' => [
        'name'             => 'Warpriest',
        'category'         => 'hybrid',
        'parent_classes'   => ['cleric', 'fighter'],
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 2,
        'spellcasting'     => 'divine (prepared, up to 6th level)',
        'alignment'        => 'Within one step of deity',
    ],

    'kineticist' => [
        'name'             => 'Kineticist',
        'category'         => 'occult',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'psychic spell-like (kinetic blast / wild talents)',
        'alignment'        => 'Any',
    ],

    'medium' => [
        'name'             => 'Medium',
        'category'         => 'occult',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'psychic (spontaneous, up to 4th or 6th level)',
        'alignment'        => 'Any',
    ],

    'mesmerist' => [
        'name'             => 'Mesmerist',
        'category'         => 'occult',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 6,
        'spellcasting'     => 'psychic (spontaneous, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'occultist' => [
        'name'             => 'Occultist',
        'category'         => 'occult',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'psychic (spontaneous implements, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'psychic' => [
        'name'             => 'Psychic',
        'category'         => 'occult',
        'hit_die'          => 'd6',
        'bab_table'        => 'bab_section_3',
        'skill_ranks'      => 2,
        'spellcasting'     => 'psychic (spontaneous, up to 9th level)',
        'alignment'        => 'Any',
    ],

    'spiritualist' => [
        'name'             => 'Spiritualist',
        'category'         => 'occult',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'psychic (spontaneous, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'antipaladin' => [
        'name'             => 'Antipaladin',
        'category'         => 'alternate',
        'parent_class'     => 'paladin',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 2,
        'spellcasting'     => 'divine (prepared, up to 4th level)',
        'alignment'        => 'Chaotic Evil',
    ],

    'ninja' => [
        'name'             => 'Ninja',
        'category'         => 'alternate',
        'parent_class'     => 'rogue',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 8,
        'spellcasting'     => 'none (ki powers / ninja tricks)',
        'alignment'        => 'Any',
    ],

    'samurai' => [
        'name'             => 'Samurai',
        'category'         => 'alternate',
        'parent_class'     => 'cavalier',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none',
        'alignment'        => 'Any lawful',
    ],

    'unchained_barbarian' => [
        'name'             => 'Barbarian (Unchained)',
        'category'         => 'unchained',
        'base_class'       => 'barbarian',
        'hit_die'          => 'd12',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none',
        'alignment'        => 'Any non-lawful',
    ],

    'unchained_monk' => [
        'name'             => 'Monk (Unchained)',
        'category'         => 'unchained',
        'base_class'       => 'monk',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none (ki powers)',
        'alignment'        => 'Any lawful',
    ],

    'unchained_rogue' => [
        'name'             => 'Rogue (Unchained)',
        'category'         => 'unchained',
        'base_class'       => 'rogue',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 8,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'unchained_summoner' => [
        'name'             => 'Summoner (Unchained)',
        'category'         => 'unchained',
        'base_class'       => 'summoner',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 2,
        'spellcasting'     => 'arcane (spontaneous, up to 6th level)',
        'alignment'        => 'Any',
    ],

    'adept' => [
        'name'             => 'Adept',
        'category'         => 'npc',
        'hit_die'          => 'd6',
        'bab_table'        => 'bab_section_3',
        'skill_ranks'      => 2,
        'spellcasting'     => 'divine (prepared, up to 5th level)',
        'alignment'        => 'Any',
    ],

    'aristocrat' => [
        'name'             => 'Aristocrat',
        'category'         => 'npc',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 4,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'commoner' => [
        'name'             => 'Commoner',
        'category'         => 'npc',
        'hit_die'          => 'd6',
        'bab_table'        => 'bab_section_3',
        'skill_ranks'      => 2,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'expert' => [
        'name'             => 'Expert',
        'category'         => 'npc',
        'hit_die'          => 'd8',
        'bab_table'        => 'bab_section_2',
        'skill_ranks'      => 6,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

    'warrior_npc' => [
        'name'             => 'Warrior (NPC)',
        'category'         => 'npc',
        'hit_die'          => 'd10',
        'bab_table'        => 'bab_section_1',
        'skill_ranks'      => 2,
        'spellcasting'     => 'none',
        'alignment'        => 'Any',
    ],

];
