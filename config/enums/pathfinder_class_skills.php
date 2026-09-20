<?php

/*
|--------------------------------------------------------------------------
| Pathfinder 1e Class Skills (Resmi Sınıf Becerileri Listesi)
|--------------------------------------------------------------------------
| Her sınıfın resmi Pathfinder RPG Core & Base & Hybrid kural kitaplarındaki
| sınıf becerileri (Class Skills) tanımlarıdır.
| Sınıf becerisine en az 1 rank verildiğinde +3 profesyonellik bonusu eklenir.
*/

return [
    'barbarian' => [
        'acrobatics', 'climb', 'craft1', 'craft2',
        'handle_animal', 'intimidate', 'know_nature', 'perception',
        'ride', 'survival', 'swim'
    ],
    'bard' => [
        'acrobatics', 'appraise', 'bluff', 'climb', 'craft1', 'craft2',
        'diplomacy', 'disguise', 'escape_artist', 'intimidate',
        'know_arcana', 'know_dungeon', 'know_engineer', 'know_geography',
        'know_history', 'know_local', 'know_nature', 'know_nobility',
        'know_planes', 'know_religion', 'linguistics', 'perception',
        'perform1', 'perform2', 'profession1', 'profession2', 'sense_motive',
        'sleight_of_hand', 'spellcraft', 'stealth', 'use_magic_device'
    ],
    'cleric' => [
        'appraise', 'craft1', 'craft2', 'diplomacy', 'heal',
        'know_arcana', 'know_history', 'know_nobility', 'know_planes',
        'know_religion', 'linguistics', 'profession1', 'profession2',
        'sense_motive', 'spellcraft'
    ],
    'druid' => [
        'climb', 'craft1', 'craft2', 'fly', 'handle_animal', 'heal',
        'know_geography', 'know_nature', 'perception', 'profession1', 'profession2',
        'ride', 'spellcraft', 'survival', 'swim'
    ],
    'fighter' => [
        'climb', 'craft1', 'craft2', 'handle_animal', 'intimidate',
        'know_dungeon', 'know_engineer', 'profession1', 'profession2',
        'ride', 'survival', 'swim'
    ],
    'monk' => [
        'acrobatics', 'climb', 'craft1', 'craft2', 'escape_artist',
        'intimidate', 'know_history', 'know_religion', 'perception',
        'perform1', 'perform2', 'profession1', 'profession2', 'ride',
        'sense_motive', 'stealth', 'swim'
    ],
    'paladin' => [
        'craft1', 'craft2', 'diplomacy', 'handle_animal', 'heal',
        'know_nobility', 'know_religion', 'profession1', 'profession2',
        'ride', 'sense_motive', 'spellcraft'
    ],
    'ranger' => [
        'climb', 'craft1', 'craft2', 'handle_animal', 'heal',
        'intimidate', 'know_dungeon', 'know_geography', 'know_nature',
        'perception', 'profession1', 'profession2', 'ride', 'spellcraft',
        'stealth', 'survival', 'swim'
    ],
    'rogue' => [
        'acrobatics', 'appraise', 'bluff', 'climb', 'craft1', 'craft2',
        'diplomacy', 'disable_device', 'disguise', 'escape_artist', 'intimidate',
        'know_dungeon', 'know_local', 'linguistics', 'perception',
        'perform1', 'perform2', 'profession1', 'profession2', 'sense_motive',
        'sleight_of_hand', 'stealth', 'swim', 'use_magic_device'
    ],
    'sorcerer' => [
        'appraise', 'bluff', 'craft1', 'craft2', 'fly', 'intimidate',
        'know_arcana', 'profession1', 'profession2', 'spellcraft', 'use_magic_device'
    ],
    'wizard' => [
        'appraise', 'craft1', 'craft2', 'fly',
        'know_arcana', 'know_dungeon', 'know_engineer', 'know_geography',
        'know_history', 'know_local', 'know_nature', 'know_nobility',
        'know_planes', 'know_religion', 'linguistics', 'profession1', 'profession2',
        'spellcraft'
    ],
    'alchemist' => [
        'appraise', 'craft1', 'craft2', 'disable_device', 'fly', 'heal',
        'know_arcana', 'know_nature', 'perception', 'profession1', 'profession2',
        'sleight_of_hand', 'spellcraft', 'survival', 'use_magic_device'
    ],
    'cavalier' => [
        'bluff', 'climb', 'craft1', 'craft2', 'diplomacy',
        'handle_animal', 'intimidate', 'profession1', 'profession2',
        'ride', 'sense_motive', 'swim'
    ],
    'gunslinger' => [
        'acrobatics', 'bluff', 'climb', 'craft1', 'craft2',
        'handle_animal', 'heal', 'intimidate', 'know_engineer', 'know_local',
        'perception', 'profession1', 'profession2', 'ride', 'sleight_of_hand',
        'survival', 'swim'
    ],
    'inquisitor' => [
        'bluff', 'climb', 'craft1', 'craft2', 'diplomacy', 'disguise',
        'heal', 'intimidate', 'know_arcana', 'know_dungeon', 'know_nature',
        'know_planes', 'know_religion', 'perception', 'profession1', 'profession2',
        'ride', 'sense_motive', 'spellcraft', 'stealth', 'survival', 'swim'
    ],
    'magus' => [
        'climb', 'craft1', 'craft2', 'fly', 'intimidate',
        'know_arcana', 'know_dungeon', 'know_planes', 'profession1', 'profession2',
        'ride', 'spellcraft', 'swim'
    ],
    'omdura' => [
        'craft1', 'craft2', 'diplomacy', 'heal', 'intimidate',
        'know_arcana', 'know_history', 'know_nobility', 'know_planes',
        'know_religion', 'linguistics', 'profession1', 'profession2',
        'sense_motive', 'spellcraft'
    ],
    'oracle' => [
        'craft1', 'craft2', 'diplomacy', 'heal',
        'know_history', 'know_planes', 'know_religion',
        'profession1', 'profession2', 'sense_motive', 'spellcraft'
    ],
    'shifter' => [
        'acrobatics', 'climb', 'craft1', 'craft2', 'fly',
        'handle_animal', 'know_nature', 'perception', 'profession1', 'profession2',
        'survival', 'swim'
    ],
    'summoner' => [
        'craft1', 'craft2', 'fly', 'handle_animal',
        'know_all', 'know_arcana', 'know_dungeon', 'know_engineer',
        'know_geography', 'know_history', 'know_local', 'know_nature',
        'know_nobility', 'know_planes', 'know_religion', 'linguistics',
        'profession1', 'profession2', 'ride', 'spellcraft', 'use_magic_device'
    ],
    'vampire_hunter' => [
        'bluff', 'climb', 'craft1', 'craft2', 'heal', 'intimidate',
        'know_arcana', 'know_geography', 'know_local', 'know_religion',
        'perception', 'profession1', 'profession2', 'ride', 'sense_motive',
        'spellcraft', 'stealth', 'survival', 'swim'
    ],
    'vigilante' => [
        'acrobatics', 'appraise', 'bluff', 'climb', 'craft1', 'craft2',
        'diplomacy', 'disable_device', 'disguise', 'escape_artist', 'intimidate',
        'know_dungeon', 'know_engineering', 'know_history', 'know_local',
        'know_nobility', 'perception', 'perform1', 'perform2', 'profession1',
        'profession2', 'sense_motive', 'sleight_of_hand', 'stealth', 'survival',
        'swim', 'use_magic_device'
    ],
    'witch' => [
        'craft1', 'craft2', 'fly', 'heal',
        'know_arcana', 'know_history', 'know_nature', 'know_planes',
        'profession1', 'profession2', 'spellcraft', 'use_magic_device'
    ],
    'arcanist' => [
        'appraise', 'craft1', 'craft2', 'fly',
        'know_arcana', 'know_dungeon', 'know_engineer', 'know_geography',
        'know_history', 'know_local', 'know_nature', 'know_nobility',
        'know_planes', 'know_religion', 'linguistics', 'profession1', 'profession2',
        'spellcraft', 'use_magic_device'
    ],
    'bloodrager' => [
        'acrobatics', 'climb', 'craft1', 'craft2', 'handle_animal',
        'intimidate', 'know_arcana', 'perception', 'ride', 'spellcraft',
        'survival', 'swim'
    ],
    'brawler' => [
        'acrobatics', 'climb', 'craft1', 'craft2', 'escape_artist',
        'handle_animal', 'intimidate', 'know_dungeon', 'know_local',
        'perception', 'profession1', 'profession2', 'ride', 'sense_motive', 'swim'
    ],
    'hunter' => [
        'climb', 'craft1', 'craft2', 'handle_animal', 'heal',
        'intimidate', 'know_dungeon', 'know_geography', 'know_nature',
        'perception', 'profession1', 'profession2', 'ride', 'spellcraft',
        'stealth', 'survival', 'swim'
    ],
    'investigator' => [
        'acrobatics', 'appraise', 'bluff', 'climb', 'craft1', 'craft2',
        'diplomacy', 'disable_device', 'disguise', 'escape_artist', 'heal',
        'intimidate', 'know_arcana', 'know_dungeon', 'know_engineer',
        'know_geography', 'know_history', 'know_local', 'know_nature',
        'know_nobility', 'know_planes', 'know_religion', 'linguistics',
        'perception', 'perform1', 'perform2', 'profession1', 'profession2',
        'sense_motive', 'sleight_of_hand', 'spellcraft', 'stealth',
        'use_magic_device'
    ],
    'shaman' => [
        'craft1', 'craft2', 'diplomacy', 'fly', 'handle_animal',
        'heal', 'know_nature', 'know_planes', 'know_religion', 'profession1',
        'profession2', 'ride', 'spellcraft', 'survival'
    ],
    'skald' => [
        'acrobatics', 'appraise', 'bluff', 'climb', 'craft1', 'craft2',
        'diplomacy', 'escape_artist', 'handle_animal', 'intimidate',
        'know_all', 'know_arcana', 'know_dungeon', 'know_engineer',
        'know_geography', 'know_history', 'know_local', 'know_nature',
        'know_nobility', 'know_planes', 'know_religion', 'linguistics',
        'perception', 'perform1', 'perform2', 'profession1', 'profession2',
        'ride', 'sense_motive', 'spellcraft', 'swim', 'use_magic_device'
    ],
    'slayer' => [
        'acrobatics', 'bluff', 'climb', 'craft1', 'craft2',
        'disguise', 'heal', 'intimidate', 'know_dungeon', 'know_geography',
        'know_local', 'perception', 'profession1', 'profession2', 'ride',
        'sense_motive', 'stealth', 'survival', 'swim'
    ],
    'swashbuckler' => [
        'acrobatics', 'bluff', 'climb', 'craft1', 'craft2',
        'diplomacy', 'escape_artist', 'intimidate', 'perception',
        'perform1', 'perform2', 'profession1', 'profession2', 'ride',
        'sense_motive', 'swim'
    ],
    'warpriest' => [
        'climb', 'craft1', 'craft2', 'diplomacy', 'handle_animal',
        'heal', 'intimidate', 'know_engineer', 'know_history', 'know_nobility',
        'know_religion', 'profession1', 'profession2', 'ride', 'sense_motive',
        'spellcraft', 'survival', 'swim'
    ],
    'kineticist' => [
        'acrobatics', 'craft1', 'craft2', 'heal', 'intimidate',
        'perception', 'profession1', 'profession2', 'stealth', 'use_magic_device'
    ],
];
