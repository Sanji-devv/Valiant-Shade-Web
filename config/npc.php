<?php

$appUrl = (string) env('APP_URL', 'http://localhost');
$appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';

$npcCanonical = env('NPC_CANONICAL_DOMAIN') ?: "npc.{$appHost}";
$npcAlias = env('NPC_ALIAS_DOMAIN') ?: "npcs.{$appHost}";

$npcAliasDomains = $npcAlias !== $npcCanonical
    ? array_values(array_unique([$npcAlias]))
    : [];

return [
    /*
    | Kanonik: npc.{domain} — npcs.{domain} aynı içeriğe 301 ile yönlendirilir.
    | Ana domain /npc-roster yolu da çalışmaya devam eder.
    */
    'domain' => $npcCanonical,
    'alias_domains' => $npcAliasDomains,
    'domains' => array_values(array_unique(array_filter([$npcCanonical, ...$npcAliasDomains]))),

    'title' => 'NPC',
    'motto' => 'Kim kimdir, nerede durur, hangi unvanı taşır.',
    'tagline' => 'Alem genelinden yüzler, unvanlar ve kayıt kütükleri. Giriş yapmadan okuyun, NPC gönderin.',
    'crest' => 'static/valiant-shade/assets/img/news/guild_hall_council.jpg',
];