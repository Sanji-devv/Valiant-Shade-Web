<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\CharacterFeat;
use App\Models\CharacterInfo;
use App\Models\CharacterProfile;
use App\Models\FinancialLedger;
use App\Models\GuildPermanentAsset;
use App\Models\GuildSetting;
use App\Models\GuildVault;
use App\Models\GuildVaultItem;
use App\Models\Quest;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CompleteMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ini_set('memory_limit', '512M');

        $primaryDir = is_dir(database_path('data/jsons/')) ? database_path('data/jsons/') : database_path('data/excel_jsons/');
        $utilityDir = is_dir('C:/Users/erdem/Desktop/PROJECTS/Utility/JSONS/') ? 'C:/Users/erdem/Desktop/PROJECTS/Utility/JSONS/' : 'C:/Users/erdem/Desktop/PROJECTS/Utility/EXCEL_JSONS/';
        $baseDir = is_dir($primaryDir) ? $primaryDir : (is_dir($utilityDir) ? $utilityDir : null);
        if (!$baseDir) {
            $this->command?->info("JSON data directory does not exist or has already been processed.");
            return;
        }

        // Ensure default Guild Vault exists
        $vault = GuildVault::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Valiant Shade Lonca Kasası',
                'balance_sp' => 30000.00,
                'notes' => 'Parti ve loncanın fiziki nakit kasası.',
            ]
        );

        $charactersByName = Character::all()->keyBy('name');
        $rankAday = Rank::where('name', 'Aday')->first();

        // Fallback user for unmapped characters
        $fallbackUser = User::firstOrCreate(
            ['email' => 'adventurers@valiantshade.com'],
            [
                'name' => 'Valiant Adventurer',
                'real_name' => 'Valiant Adventurer',
                'username' => 'valiant_adventurer',
                'password' => bcrypt('123456'),
                'password_hash' => bcrypt('123456'),
                'role' => 'PLAYER',
                'status' => 1,
            ]
        );

        $getOrCreateChar = function (string $charName) use (&$charactersByName, $fallbackUser, $rankAday): ?Character {
            $charName = trim($charName);
            if ($charName === '' || $charName === '-' || $charName === 'Yok' || strlen($charName) > 100) {
                return null;
            }
            if ($charactersByName->has($charName)) {
                return $charactersByName->get($charName);
            }
            $char = Character::create([
                'user_id' => $fallbackUser->id,
                'name' => $charName,
                'character_type' => 'MAIN',
                'status' => 'ACTIVE',
                'rank_id' => $rankAday?->id,
                'current_level' => 1,
                'allowance_sp' => 0.00,
                'total_xp' => 0.00,
            ]);
            $charactersByName->put($charName, $char);
            return $char;
        };

        // -------------------------------------------------------------
        // 1. PROCESS: Envanter.json
        // -------------------------------------------------------------
        $envFile = $baseDir . 'Envanter.json';
        if (file_exists($envFile)) {
            $envData = json_decode(file_get_contents($envFile), true) ?: [];
            $this->command?->info("Processing Envanter.json (" . count($envData) . " rows)...");

            foreach ($envData as $idx => $row) {
                if ($idx === 0) continue; // Skip header row

                // 1a. Character Allowances (Column A -> Ödenek (SP))
                $charName = trim((string)($row['column_A'] ?? ''));
                if ($charName !== '' && isset($row['Ödenek (SP)']) && is_numeric($row['Ödenek (SP)'])) {
                    $allowance = (float)$row['Ödenek (SP)'];
                    $char = $getOrCreateChar($charName);
                    if ($char) {
                        $char->update(['allowance_sp' => $allowance]);
                        FinancialLedger::firstOrCreate(
                            [
                                'character_id' => $char->id,
                                'description' => 'Envanter Master Sheet Başlangıç Ödenek Bakiyesi',
                            ],
                            [
                                'amount_sp' => $allowance,
                                'balance_after_sp' => $allowance,
                                'transaction_type' => 'DEPOSIT',
                            ]
                        );
                    }
                }

                // 1b. General Vault Items (Column D..K)
                $itemName = trim((string)($row['column_D'] ?? ''));
                if ($itemName !== '' && $itemName !== 'Envanter') {
                    $qty = max(1, (int)($row['column_G'] ?? 1));
                    $unitPrice = (float)($row['column_H'] ?? 0);
                    $totalPrice = (float)($row['column_I'] ?? ($qty * $unitPrice));
                    $marketVal = (float)($row['3595124.5'] ?? round($totalPrice * 0.55, 2));
                    $buyPrice = (float)($row['column_K'] ?? round($unitPrice * 0.55, 2));
                    $itemType = trim((string)($row['column_F'] ?? 'Misc')) ?: 'Misc';
                    $quality = trim((string)($row['column_E'] ?? '')) ?: null;

                    GuildVaultItem::updateOrCreate(
                        [
                            'item_name' => $itemName,
                        ],
                        [
                            'quality' => $quality,
                            'item_type' => $itemType,
                            'quantity' => $qty,
                            'unit_price_sp' => $unitPrice,
                            'total_price_sp' => $totalPrice,
                            'market_value_sp' => $marketVal,
                            'purchase_cost_sp' => $buyPrice,
                            'is_in_vault' => true,
                        ]
                    );
                }

                // 1c. Permanent Assets (Column M..Q)
                $assetName = trim((string)($row['column_M'] ?? ''));
                if ($assetName !== '' && $assetName !== 'Kalıcı Envanter') {
                    $pQty = max(1, (int)($row['column_N'] ?? 1));
                    $pUnit = (float)($row['column_O'] ?? 0);
                    $pTotal = (float)($row['column_P'] ?? ($pQty * $pUnit));
                    $pMarket = (float)($row['column_Q'] ?? round($pTotal * 0.55, 2));

                    $assetType = 'PROPERTY';
                    if (str_contains(strtolower($assetName), 'horse') || str_contains(strtolower($assetName), 'at') || str_contains(strtolower($assetName), 'dog') || str_contains(strtolower($assetName), 'dizgin')) {
                        $assetType = 'MOUNT';
                    } elseif (str_contains(strtolower($assetName), 'araba') || str_contains(strtolower($assetName), 'cart') || str_contains(strtolower($assetName), 'gemi') || str_contains(strtolower($assetName), 'tekne')) {
                        $assetType = 'VEHICLE';
                    } elseif (str_contains(strtolower($assetName), 'dükkan') || str_contains(strtolower($assetName), 'altar') || str_contains(strtolower($assetName), 'tarla') || str_contains(strtolower($assetName), 'oda')) {
                        $assetType = 'PROPERTY';
                    } elseif (str_contains(strtolower($assetName), 'sözleşme') || str_contains(strtolower($assetName), 'kontrat')) {
                        $assetType = 'DEED_CONTRACT';
                    } else {
                        $assetType = 'RELIC';
                    }

                    GuildPermanentAsset::updateOrCreate(
                        [
                            'asset_name' => $assetName,
                        ],
                        [
                            'quantity' => $pQty,
                            'unit_price_sp' => $pUnit,
                            'total_price_sp' => $pTotal,
                            'asset_category' => $assetType,
                            'notes_and_lore' => "Adet: {$pQty}, Birim: {$pUnit} SP, Tutar: {$pTotal} SP, Ederi: {$pMarket} SP",
                        ]
                    );
                }

                // 1d. Shadow Vault Items (Column S..U)
                $shadowName = trim((string)($row['Reçine (L)'] ?? ''));
                if ($shadowName !== '' && $shadowName !== 'Shadow Envanter') {
                    $sQty = max(1, (int)($row['column_T'] ?? 1));
                    $sType = trim((string)($row['16.0'] ?? 'Shadow')) ?: 'Shadow';

                    GuildVaultItem::updateOrCreate(
                        [
                            'item_name' => $shadowName,
                        ],
                        [
                            'item_type' => 'Shadow',
                            'quality' => $sType,
                            'quantity' => $sQty,
                            'unit_price_sp' => 0.00,
                            'total_price_sp' => 0.00,
                            'market_value_sp' => 0.00,
                            'purchase_cost_sp' => 0.00,
                            'is_in_vault' => true,
                        ]
                    );
                }
            }
        }

        // -------------------------------------------------------------
        // 2. PROCESS: Feats List.json
        // -------------------------------------------------------------
        $featsFile = $baseDir . 'Feats List.json';
        if (file_exists($featsFile)) {
            $featsData = json_decode(file_get_contents($featsFile), true) ?: [];
            $this->command?->info("Processing Feats List.json (" . count($featsData) . " characters)...");

            foreach ($featsData as $fRow) {
                $charName = trim((string)($fRow['Character Name'] ?? ''));
                if ($charName === '' || $charName === 'Character Name') continue;

                $char = $getOrCreateChar($charName);
                if (!$char) continue;

                $profile = CharacterProfile::firstOrCreate(
                    ['character_id' => $char->id],
                    [
                        'character_name' => $char->name,
                        'user_id' => $char->user_id,
                        'level' => $char->current_level,
                        'character_type' => $char->character_type,
                    ]
                );
                $charInfo = CharacterInfo::firstOrCreate(['character_id' => $char->id]);

                // Update Class & Archetype if present
                $classArchetype = trim((string)($fRow['Class (Archetype)'] ?? ''));
                if ($classArchetype !== '' && $classArchetype !== '-') {
                    $charInfo->class_and_level = $classArchetype;
                }

                $existingFeats = is_array($charInfo->feats_and_traits) ? $charInfo->feats_and_traits : [];
                $existingGear = is_array($charInfo->equipped_gear) ? $charInfo->equipped_gear : [];

                foreach ($fRow as $key => $val) {
                    if (!$val || $val === '-' || is_numeric($val)) continue;
                    $valStr = trim((string)$val);

                    // Feats by level
                    if (str_contains($key, 'Level') || str_contains($key, 'Feat')) {
                        if (!in_array($valStr, $existingFeats)) {
                            $existingFeats[] = $valStr;
                        }

                        preg_match('/\d+/', (string)$key, $mLevel);
                        $lvlNum = !empty($mLevel[0]) ? (int)$mLevel[0] : 1;

                        CharacterFeat::firstOrCreate(
                            [
                                'character_name' => $char->name,
                                'feat_name' => $valStr,
                                'level' => $lvlNum,
                            ],
                            [
                                'slot_type' => str_contains(strtolower((string)$key), 'bonus') ? 'class_bonus' : 'standard',
                                'notes' => "Seviye Edinimi: {$key}",
                            ]
                        );
                    }

                    // Kingdom Items (Isonburg Krallığı'ndan Alınan Eşya)
                    if (str_contains($key, 'Isonburg') && $valStr !== '-') {
                        if (!in_array($valStr, $existingGear)) {
                            $existingGear[] = [
                                'slot' => 'Kingdom Reward',
                                'item' => $valStr,
                                'tier' => $key,
                            ];
                        }
                    }
                }

                $charInfo->feats_and_traits = $existingFeats;
                $charInfo->equipped_gear = $existingGear;
                $charInfo->save();
            }
        }

        // -------------------------------------------------------------
        // 3. PROCESS: Lonca Spellbook.json
        // -------------------------------------------------------------
        $spellFile = $baseDir . 'Lonca Spellbook.json';
        if (file_exists($spellFile)) {
            $spellData = json_decode(file_get_contents($spellFile), true) ?: [];
            $this->command?->info("Processing Lonca Spellbook.json (" . count($spellData) . " rows)...");

            $goldMultiplier = [
                '5 Gold' => 50,
                '10 Gold' => 100,
                '40 Gold' => 400,
                '90 Gold' => 900,
                '160 Gold' => 1600,
                '250 Gold' => 2500,
                '360 Gold' => 3600,
                '490 Gold' => 4900,
                '640 Gold' => 6400,
                '810 Gold' => 8100,
            ];

            foreach ($spellData as $idx => $sRow) {
                if ($idx === 0) continue; // Header row

                foreach ($sRow as $costKey => $spellName) {
                    $spellName = trim((string)$spellName);
                    if ($spellName === '' || str_starts_with($spellName, 'Level')) continue;

                    $spCost = (float)($goldMultiplier[$costKey] ?? 100);

                    GuildVaultItem::firstOrCreate(
                        [
                            'item_name' => "Scroll: {$spellName}",
                        ],
                        [
                            'item_type' => 'Scroll',
                            'quality' => $costKey,
                            'quantity' => 1,
                            'unit_price_sp' => $spCost,
                            'total_price_sp' => $spCost,
                            'market_value_sp' => round($spCost * 0.55, 2),
                            'purchase_cost_sp' => $spCost,
                            'is_in_vault' => true,
                        ]
                    );
                }
            }
        }

        // -------------------------------------------------------------
        // 4. PROCESS: Görev Listesi.json
        // -------------------------------------------------------------
        $listFile = $baseDir . 'Görev Listesi.json';
        if (!file_exists($listFile)) {
            $files = glob($baseDir . '*rev Listesi.json');
            $listFile = $files[0] ?? null;
        }

        if ($listFile && file_exists($listFile)) {
            $listData = json_decode(file_get_contents($listFile), true) ?: [];
            $this->command?->info("Processing Görev Listesi.json (" . count($listData) . " quests)...");

            foreach ($listData as $qRow) {
                // Main Quests
                $mainTitle = trim((string)($qRow['Main Quests'] ?? ''));
                if ($mainTitle !== '' && $mainTitle !== 'Main Quests') {
                    Quest::firstOrCreate(
                        ['title' => $mainTitle],
                        [
                            'is_main_quest' => true,
                            'level' => 1,
                            'base_xp' => 1000.00,
                            'status' => 'PENDING',
                            'report_summary' => "Görev Panosu Uygunluk: " . ($qRow['Uygunluk'] ?? 'Kıdemli/Uzman'),
                        ]
                    );
                }

                // Side Quests
                $sideTitle = trim((string)($qRow['Side Quests'] ?? ''));
                if ($sideTitle !== '' && $sideTitle !== 'Side Quests') {
                    Quest::firstOrCreate(
                        ['title' => $sideTitle],
                        [
                            'is_main_quest' => false,
                            'level' => 1,
                            'base_xp' => 750.00,
                            'status' => 'PENDING',
                            'report_summary' => "Görev Panosu Uygunluk: " . ($qRow['Uygunluk_2'] ?? 'Aday/Üye'),
                        ]
                    );
                }

                // Standing Quests
                $standingTitle = trim((string)($qRow['Sürekli Görev'] ?? ''));
                if ($standingTitle !== '' && $standingTitle !== 'Sürekli Görev') {
                    Quest::firstOrCreate(
                        ['title' => $standingTitle],
                        [
                            'is_main_quest' => false,
                            'level' => 1,
                            'base_xp' => 750.00,
                            'status' => 'PENDING',
                            'report_summary' => "Sürekli Görev Uygunluk: " . ($qRow['Uygunluk_3'] ?? 'Kıdemli/Uzman'),
                        ]
                    );
                }

                // Archived Quests
                $archivedTitle = trim((string)($qRow['Yürürlük Dışı Görev'] ?? ''));
                if ($archivedTitle !== '' && $archivedTitle !== 'Yürürlük Dışı Görev') {
                    Quest::firstOrCreate(
                        ['title' => $archivedTitle],
                        [
                            'is_main_quest' => false,
                            'level' => 1,
                            'base_xp' => 750.00,
                            'status' => 'DRAFT',
                            'report_summary' => "Arşivlenmiş Görev Uygunluk: " . ($qRow['Uygunluk_4'] ?? 'Kıdemli/Uzman'),
                        ]
                    );
                }
            }
        }

        // -------------------------------------------------------------
        // 5. PROCESS: Muhasebe Track.json
        // -------------------------------------------------------------
        $muhFile = $baseDir . 'Muhasebe Track.json';
        if (file_exists($muhFile)) {
            $muhData = json_decode(file_get_contents($muhFile), true) ?: [];
            $this->command?->info("Processing Muhasebe Track.json (" . count($muhData) . " entries)...");

            foreach ($muhData as $mRow) {
                // Character salary/stipend payouts (column_T: name, column_V: amount)
                $pCharName = trim((string)($mRow['column_T'] ?? ''));
                $pAmount = isset($mRow['column_V']) && is_numeric($mRow['column_V']) ? (float)$mRow['column_V'] : null;

                if ($pCharName !== '' && $pAmount !== null && $pAmount > 0 && $pCharName !== 'Ubeydullah' && $pCharName !== 'Kaydı Olmayanlar') {
                    $char = $charactersByName->get($pCharName);
                    if ($char) {
                        FinancialLedger::firstOrCreate(
                            [
                                'character_id' => $char->id,
                                'description' => "Muhasebe Defteri Maaş/Ödenek: " . ($mRow['column_U'] ?? 'Hizmet'),
                            ],
                            [
                                'amount_sp' => $pAmount,
                                'balance_after_sp' => $char->allowance_sp,
                                'transaction_type' => 'SALARY',
                            ]
                        );
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // 6. PROCESS: Rapor Alıcı Tab.json
        // -------------------------------------------------------------
        $raporFiles = glob($baseDir . 'Rapor Al*Tab.json');
        $raporFile = $raporFiles[0] ?? null;
        if ($raporFile && file_exists($raporFile)) {
            $raporData = json_decode(file_get_contents($raporFile), true) ?: [];
            $this->command?->info("Processing Rapor Alıcı Tab.json (" . count($raporData) . " narrative reports)...");

            foreach ($raporData as $rRow) {
                $qTitle = '';
                foreach ($rRow as $k => $v) {
                    if (str_contains($k, 'rev Ad')) {
                        $qTitle = trim((string)$v);
                        break;
                    }
                }

                if ($qTitle === '') continue;

                $reportText = trim((string)($rRow['Rapor Metni'] ?? ''));
                $lootFound = trim((string)($rRow['Görevde Bulunan  Eşyalar'] ?? ''));

                $quest = Quest::where('title', 'ILIKE', "%{$qTitle}%")->first();
                if ($quest) {
                    if ($reportText !== '' && (empty($quest->full_report) || strlen($quest->full_report) < strlen($reportText))) {
                        $quest->full_report = $reportText;
                    }
                    if ($lootFound !== '' && $lootFound !== 'Yok' && $lootFound !== '-') {
                        $quest->report_summary = ($quest->report_summary ? $quest->report_summary . " | Bulunan Ganimet: " : "Bulunan Ganimet: ") . $lootFound;
                    }
                    $quest->save();
                }
            }
        }

        // -------------------------------------------------------------
        // 7. PROCESS: Öneri Şikayet.json
        // -------------------------------------------------------------
        $oneriFiles = glob($baseDir . '*neri *ikayet.json');
        $oneriFile = $oneriFiles[0] ?? ($baseDir . 'Öneri Şikayet.json');
        if (file_exists($oneriFile)) {
            $oneriData = json_decode(file_get_contents($oneriFile), true) ?: [];
            $this->command?->info("Processing Öneri Şikayet.json (" . count($oneriData) . " feedback items)...");
            GuildSetting::set('guild_suggestions_and_complaints', json_encode($oneriData, JSON_UNESCAPED_UNICODE));
        }

        // -------------------------------------------------------------
        // 8. PROCESS: GM Araçları.json
        // -------------------------------------------------------------
        $gmFiles = glob($baseDir . 'GM Ara*lar*.json');
        $gmFile = $gmFiles[0] ?? ($baseDir . 'GM Araçları.json');
        if (file_exists($gmFile)) {
            $gmData = json_decode(file_get_contents($gmFile), true) ?: [];
            $this->command?->info("Processing GM Araçları.json (" . count($gmData) . " records)...");
            GuildSetting::set('gm_tools_config', json_encode($gmData, JSON_UNESCAPED_UNICODE));
        }

        // -------------------------------------------------------------
        // 9. PROCESS: Son Oyun Track, InaktifFinder, OyunSayacı
        // -------------------------------------------------------------
        $sonOyunFiles = glob($baseDir . 'Son Oyun*.json');
        $sonOyunFile = $sonOyunFiles[0] ?? null;

        $inaktifFiles = glob($baseDir . 'Inaktif*.json');
        $inaktifFile = $inaktifFiles[0] ?? null;

        $oyunSayacFiles = glob($baseDir . 'OyunSayac*.json');
        $oyunSayacFile = $oyunSayacFiles[0] ?? null;

        $activityData = [];
        if ($sonOyunFile && file_exists($sonOyunFile)) {
            $activityData['last_game_tracking'] = json_decode(file_get_contents($sonOyunFile), true) ?: [];
            $this->command?->info("Processing Son Oyun Track.json (" . count($activityData['last_game_tracking']) . " rows)...");
        }
        if ($inaktifFile && file_exists($inaktifFile)) {
            $activityData['inactivity_finder'] = json_decode(file_get_contents($inaktifFile), true) ?: [];
            $this->command?->info("Processing InaktifFinder.json (" . count($activityData['inactivity_finder']) . " rows)...");
        }
        if ($oyunSayacFile && file_exists($oyunSayacFile)) {
            $activityData['game_counter'] = json_decode(file_get_contents($oyunSayacFile), true) ?: [];
            $this->command?->info("Processing OyunSayacı.json (" . count($activityData['game_counter']) . " rows)...");
        }

        if (!empty($activityData)) {
            GuildSetting::set('guild_activity_and_session_tracking', json_encode($activityData, JSON_UNESCAPED_UNICODE));
        }

        // -------------------------------------------------------------
        // 10. VERIFY DATABASE COUNTS & FINALIZE DELETION
        // -------------------------------------------------------------
        $vaultItemsCount = GuildVaultItem::count();
        $assetsCount = GuildPermanentAsset::count();
        $featsCount = CharacterFeat::count();
        $questsCount = Quest::count();
        $ledgerCount = FinancialLedger::count();

        $this->command?->info("--- DATABASE VERIFICATION SUMMARY ---");
        $this->command?->info("Guild Vault Items: {$vaultItemsCount}");
        $this->command?->info("Guild Permanent Assets: {$assetsCount}");
        $this->command?->info("Character Feats: {$featsCount}");
        $this->command?->info("Quests: {$questsCount}");
        $this->command?->info("Financial Ledger Entries: {$ledgerCount}");

        if ($vaultItemsCount > 0 && $assetsCount > 0 && $featsCount > 0 && $questsCount > 0 && is_dir($primaryDir)) {
            File::deleteDirectory($primaryDir);
            $this->command?->info("SUCCESS: database/data/excel_jsons directory has been processed completely and deleted!");
        } else {
            $this->command?->warn("NOTICE: excel_jsons directory not deleted because counts were below threshold or directory already removed.");
        }
    }
}
