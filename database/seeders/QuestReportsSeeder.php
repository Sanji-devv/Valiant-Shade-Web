<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\Quest;
use App\Models\Rank;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestReportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ini_set('memory_limit', '512M');

        $primaryPath = file_exists('C:/Users/erdem/Desktop/PROJECTS/Utility/JSONS/Görev Raporları.json')
            ? 'C:/Users/erdem/Desktop/PROJECTS/Utility/JSONS/Görev Raporları.json'
            : 'C:/Users/erdem/Desktop/PROJECTS/Utility/EXCEL_JSONS/Görev Raporları.json';
        $fallbackPath = file_exists(database_path('data/jsons/Görev Raporları.json'))
            ? database_path('data/jsons/Görev Raporları.json')
            : database_path('data/excel_jsons/Görev Raporları.json');

        $questFile = file_exists($primaryPath) ? $primaryPath : $fallbackPath;
        if (!file_exists($questFile)) {
            $this->command?->error("Görev Raporları.json not found at {$questFile}");
            return;
        }

        $questData = json_decode(file_get_contents($questFile), true) ?: [];

        // 1. Clear all existing quest reports and participants
        DB::statement('TRUNCATE TABLE quest_participants, quests RESTART IDENTITY CASCADE');

        $rankAday = Rank::where('name', 'Aday')->first();

        // 2. Get or create fallback user for unknown adventurer characters
        $fallbackUser = User::firstOrCreate(
            ['email' => 'adventurers@valiantshade.com'],
            [
                'name' => 'Valiant Adventurer',
                'display_name' => 'Valiant Adventurer',
                'username' => 'valiant_adventurer',
                'password' => bcrypt('123456'),
                'password_hash' => bcrypt('123456'),
                'role' => 'PLAYER',
                'status' => 1,
            ]
        );

        // 3. Cache all characters by name
        $characterCache = Character::all()->keyBy('name');

        $ensureCharacter = function (string $charName) use (&$characterCache, $fallbackUser, $rankAday): ?Character {
            $charName = trim($charName);
            if ($charName === '' || $charName === '-' || $charName === 'Yok' || strlen($charName) > 100) {
                return null;
            }

            if ($characterCache->has($charName)) {
                return $characterCache->get($charName);
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

            $characterCache->put($charName, $char);
            return $char;
        };

        // Participant columns in Görev Raporları.json
        $participantCols = [
            'Diğer Oyuncular', 'column_AP', 'column_AQ', 'column_AR', 'column_AS',
            'column_AT', 'column_AU', 'column_AV', 'column_AW', 'column_AX',
            'column_AY', 'column_AZ', 'column_BA', 'column_BB', 'column_BC',
            'column_BD', 'column_BE',
        ];

        $jsonReports = [];
        $reportId = 1;

        // 4. Process all quest reports one by one
        foreach ($questData as $q) {
            $title = trim((string)($q['Görev adı'] ?? ''));
            if ($title === '' || $title === 'Görev adı') {
                continue;
            }

            $dateRaw = trim((string)($q['Tarih'] ?? ''));
            $questDate = null;
            if ($dateRaw !== '') {
                try {
                    $questDate = Carbon::parse($dateRaw)->toDateString();
                } catch (\Throwable $e) {
                    $questDate = null;
                }
            }

            $mqVal = $q['MQ'] ?? '';
            $isEpic = ($mqVal === '✓✓' || $mqVal === '🗸🗸');
            $isMain = ($isEpic || $mqVal === '✓' || $mqVal === '🗸' || $mqVal === '1' || $mqVal === true || strtolower((string)$mqVal) === 'yes');
            $mq = $isEpic ? 'epic' : ($isMain ? 'main' : 'none');
            $mqMarker = $isEpic ? '✓✓' : ($isMain ? '✓' : '✗');
            $tierType = $isEpic ? 'epic' : ($isMain ? 'main' : 'side');

            $level = max(1, (int)($q['Level'] ?? 1));
            $baseXp = (float)($q['XP'] ?? 0);
            $depoCut = (float)($q['Depo'] ?? 0);
            $guildCut = (float)($q['Lonca'] ?? 0);
            $raporCut = (float)($q['Rapor'] ?? 0);
            $bonusPoolXp = (float)($q['Ek'] ?? 0);
            $perPlayerXp = (float)($q['Ortalama'] ?? 0);
            $netPoolXp = round($baseXp - $depoCut - $guildCut + $bonusPoolXp, 2);

            $gmName = trim((string)($q['GM'] ?? ''));
            $gmChar = $gmName !== '' ? $ensureCharacter($gmName) : null;

            $reporterName = trim((string)($q['Raporlayan'] ?? ''));
            $reporterChar = $reporterName !== '' ? $ensureCharacter($reporterName) : null;

            $quest = Quest::create([
                'title' => $title,
                'quest_date' => $questDate,
                'is_main_quest' => $isMain,
                'level' => $level,
                'base_xp' => $baseXp,
                'depo_fee_xp' => $depoCut,
                'guild_cut_xp' => $guildCut,
                'report_reward_xp' => $raporCut,
                'bonus_pool_xp' => $bonusPoolXp,
                'net_pool_xp' => $netPoolXp,
                'per_player_xp' => $perPlayerXp,
                'gm_character_id' => $gmChar?->id,
                'reporter_character_id' => $reporterChar?->id,
                'status' => 'APPROVED',
            ]);

            $participantsMap = [];
            $otherPlayers = [];

            // Reporter is participant #1
            if ($reporterChar) {
                $participantsMap[$reporterChar->id] = [
                    'quest_id' => $quest->id,
                    'character_id' => $reporterChar->id,
                    'earned_quest_xp' => $perPlayerXp,
                    'earned_report_xp' => $raporCut,
                    'is_kia' => false,
                    'is_mia' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Other participants
            foreach ($participantCols as $col) {
                $pName = trim((string)($q[$col] ?? ''));
                if ($pName === '' || $pName === '-' || $pName === 'Yok') {
                    continue;
                }
                $otherPlayers[] = $pName;
                $pChar = $ensureCharacter($pName);
                if ($pChar && !isset($participantsMap[$pChar->id])) {
                    $participantsMap[$pChar->id] = [
                        'quest_id' => $quest->id,
                        'character_id' => $pChar->id,
                        'earned_quest_xp' => $perPlayerXp,
                        'earned_report_xp' => 0.00,
                        'is_kia' => false,
                        'is_mia' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($participantsMap)) {
                DB::table('quest_participants')->insert(array_values($participantsMap));
            }

            $depoStatus = ($depoCut == 0);
            $playerCount = (int)($q['Oyuncu'] ?? (1 + count($otherPlayers)));

            $party = [];
            if ($reporterName !== '') {
                $party[] = [
                    'name' => $reporterName,
                    'role' => 'Reporter',
                    'class' => 'Adventurer',
                    'level' => $level,
                    'xp' => (int) round($perPlayerXp),
                ];
            }
            foreach ($otherPlayers as $other) {
                $party[] = [
                    'name' => $other,
                    'role' => 'Member',
                    'class' => 'Adventurer',
                    'level' => $level,
                    'xp' => (int) round($perPlayerXp),
                ];
            }

            // JSON representation for QuestReportService
            $jsonReports[] = [
                'id' => $reportId++,
                'title' => $title,
                'date' => $dateRaw ?: ($questDate ?? ''),
                'status' => 'success',
                'mq' => $mq,
                'mq_marker' => $mqMarker,
                'is_main' => $isMain,
                'tier_type' => $tierType,
                'level' => $level,
                'total_xp' => $baseXp,
                'depo_status' => $depoStatus,
                'guild_cut' => $guildCut,
                'depo_cut' => $depoCut,
                'rapor_cut' => $raporCut,
                'vault_cut' => $raporCut,
                'extra_reward' => $bonusPoolXp,
                'avg_reward' => $perPlayerXp,
                'player_count' => $playerCount,
                'gm' => $gmName ?: 'Shalvaz',
                'reporter' => $reporterName ?: 'Tolrun',
                'other_players' => $otherPlayers,
                'location' => 'Siwion',
                'enemies' => 'Canavarlar ve Eşkıyalar',
                'report_summary' => "{$title} görevi tamamlandı. Toplam {$baseXp} XP dağıtıldı.",
                'full_report' => "Sefer Raporu: {$title}. GM {$gmName} önderliğinde {$reporterName} ve kadro tarafından icra edildi.",
                'party' => $party,
            ];
        }

        // 5. Save all reports to storage/app/quest_reports.json for QuestReportService
        $storagePath = storage_path('app/quest_reports.json');
        $dir = dirname($storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($storagePath, json_encode($jsonReports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
