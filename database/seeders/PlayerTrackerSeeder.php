<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\CharacterGraveyard;
use App\Models\CharacterInfo;
use App\Models\PlayerXp;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlayerTrackerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $primaryDir = is_dir('C:/Users/erdem/Desktop/PROJECTS/Utility/JSONS/') ? 'C:/Users/erdem/Desktop/PROJECTS/Utility/JSONS/' : 'C:/Users/erdem/Desktop/PROJECTS/Utility/EXCEL_JSONS/';
        $fallbackDir = is_dir(database_path('data/jsons/')) ? database_path('data/jsons/') : database_path('data/excel_jsons/');
        $baseDir = is_dir($fallbackDir) ? $fallbackDir : (is_dir($primaryDir) ? $primaryDir : $fallbackDir);
        $trackerFile = $baseDir . 'Player XP Tracker.json';
        $maceraciFile = $baseDir . 'Maceracı İnfo.json';

        if (!file_exists($trackerFile)) {
            $this->command?->error("Player XP Tracker.json not found at {$trackerFile}");
            return;
        }

        $trackerData = json_decode(file_get_contents($trackerFile), true) ?: [];
        $maceraciData = file_exists($maceraciFile) ? (json_decode(file_get_contents($maceraciFile), true) ?: []) : [];

        // 1. Read allowances from Maceracı İnfo.json (columns G-H: İsim -> Ödenek(SP))
        $allowances = [];
        foreach ($maceraciData as $m) {
            $cName = trim((string)($m['İsim'] ?? ''));
            if ($cName !== '' && isset($m['Ödenek(SP)'])) {
                $allowances[$cName] = (float) $m['Ödenek(SP)'];
            }
        }

        // 2. Fetch and cache Ranks
        $ranks = Rank::all()->keyBy('name');
        $rankAday = $ranks->get('Aday');
        $rankClerk = $ranks->get('Clerk');
        $rankUye = $ranks->get('Üye');
        $rankKidemli = $ranks->get('Kıdemli');
        $rankUzman = $ranks->get('Uzman');

        // 3. Collect unique players and initialize Users
        $playerUsers = [];
        $existingUsers = User::all();

        $usernamesInUse = $existingUsers->pluck('username')->filter()->flip()->toArray();
        $emailsInUse = $existingUsers->pluck('email')->filter()->flip()->toArray();
        $passwordHash = Hash::make('123456');

        $getOrCreateUser = function (string $playerName) use (&$playerUsers, &$usernamesInUse, &$emailsInUse, $passwordHash): User {
            $playerName = trim($playerName);
            if ($playerName === '' || $playerName === 'Oyuncu' || $playerName === '?') {
                $playerName = 'Valiant Adventurer';
            }

            if (isset($playerUsers[$playerName])) {
                return $playerUsers[$playerName];
            }

            $slug = Str::slug($playerName, '_');
            if ($slug === '') {
                $slug = 'player';
            }
            $username = $slug;
            $counter = 2;
            while (isset($usernamesInUse[$username])) {
                $username = $slug . '_' . $counter++;
            }
            $usernamesInUse[$username] = true;

            $emailSlug = str_replace('_', '.', $username);
            $email = $emailSlug . '@valiantshade.com';
            $eCounter = 2;
            while (isset($emailsInUse[$email])) {
                $email = $emailSlug . '.' . $eCounter++ . '@valiantshade.com';
            }
            $emailsInUse[$email] = true;

            $user = User::create([
                'name' => $playerName,
                'display_name' => $playerName,
                'username' => $username,
                'email' => $email,
                'password' => $passwordHash,
                'password_hash' => $passwordHash,
                'role' => 'PLAYER',
                'status' => 1,
                'email_verified_at' => now(),
            ]);

            $playerUsers[$playerName] = $user;
            return $user;
        };

        // Cache characters by name to prevent re-querying
        $characterCache = Character::all()->keyBy('name');

        // 4. Process Active Characters from Sütun B-N
        foreach ($trackerData as $row) {
            $charName = trim((string)($row['İsim'] ?? ''));
            $playerName = trim((string)($row['Oyuncu'] ?? ''));

            if ($charName === '' || $charName === 'İsim' || $playerName === '' || $playerName === 'Oyuncu') {
                continue;
            }

            $user = $getOrCreateUser($playerName);
            $rawRankStr = trim((string)($row['Rütbe'] ?? ''));

            $isClerk = ($rawRankStr === 'Clerk' || str_contains(strtolower($charName), 'clerk'));
            $charType = $isClerk ? 'CLERK' : 'MAIN';

            $status = match ($rawRankStr) {
                'Inaktif' => 'INACTIVE',
                'Gezgin' => 'TRAVELER',
                default => 'ACTIVE',
            };

            $level = (int)($row['Level'] ?? 1);
            $sessions = (int)($row['Görev'] ?? 0);

            if ($isClerk) {
                $rankStr = 'Clerk';
            } elseif ($rawRankStr === 'Inaktif' || $rawRankStr === 'Gezgin') {
                $rankStr = $rawRankStr;
            } else {
                $rankStr = Character::calculateRankFromLevelAndSessions(max(1, $level), $sessions);
            }

            $matchedRank = match ($rankStr) {
                'Clerk' => $rankClerk,
                'Uzman' => $rankUzman,
                'Kıdemli' => $rankKidemli,
                'Üye' => $rankUye,
                default => $rankAday,
            };

            $rankFloorXp = (float)($row['Rütbe_2'] ?? 0);
            $questXp = (float)($row['Görev_2'] ?? 0);
            $reportXp = (float)($row['Rapor'] ?? 0);
            $bailiffXp = (float)($row['Bailiff'] ?? 0);
            $exCharXp = (float)($row['EX'] ?? 0);
            $wikiXp = (float)($row['Wiki'] ?? 0);
            $totalXp = (float)($row['XP'] ?? ($rankFloorXp + $questXp + $reportXp + $bailiffXp + $exCharXp + $wikiXp));
            $level = (int)($row['Level'] ?? 1);
            $allowanceSp = $allowances[$charName] ?? 0.00;

            /** @var Character $char */
            $char = $characterCache->get($charName);
            if (!$char) {
                $char = Character::create([
                    'user_id' => $user->id,
                    'name' => $charName,
                    'character_type' => $charType,
                    'status' => $status,
                    'rank_id' => $matchedRank?->id,
                    'current_level' => max(1, $level),
                    'allowance_sp' => $allowanceSp,
                    'rank_floor_xp' => $rankFloorXp,
                    'quest_xp' => $questXp,
                    'report_xp' => $reportXp,
                    'bailiff_xp' => $bailiffXp,
                    'ex_char_xp' => $exCharXp,
                    'wiki_xp' => $wikiXp,
                    'total_xp' => $totalXp,
                ]);
                $characterCache->put($charName, $char);
            } else {
                $char->update([
                    'user_id' => $user->id,
                    'character_type' => $charType,
                    'status' => $status,
                    'rank_id' => $matchedRank?->id,
                    'current_level' => max(1, $level),
                    'allowance_sp' => $allowanceSp > 0 ? $allowanceSp : $char->allowance_sp,
                    'rank_floor_xp' => $rankFloorXp,
                    'quest_xp' => $questXp,
                    'report_xp' => $reportXp,
                    'bailiff_xp' => $bailiffXp,
                    'ex_char_xp' => $exCharXp,
                    'wiki_xp' => $wikiXp,
                    'total_xp' => $totalXp,
                ]);
            }

            // Ensure CharacterInfo exists
            CharacterInfo::firstOrCreate(
                ['character_id' => $char->id],
                [
                    'class_and_level' => "Level {$char->current_level} Adventurer",
                    'race' => 'Human',
                    'alignment' => 'TN',
                    'hp_max' => max(10, $char->current_level * 9),
                    'hp_current' => max(10, $char->current_level * 9),
                    'armor_class_total' => 15,
                    'touch_ac' => 12,
                    'flat_footed_ac' => 13,
                    'pocket_money' => ['pp' => 0, 'gp' => round($allowanceSp / 10, 2), 'sp' => 0, 'cp' => 0],
                ]
            );

            // Assign active character on user if not set
            if ($charType === 'MAIN' && $status === 'ACTIVE' && !$user->active_main_character_id) {
                $user->update(['active_main_character_id' => $char->id]);
            } elseif ($charType === 'CLERK' && !$user->active_clerk_character_id) {
                $user->update(['active_clerk_character_id' => $char->id]);
            }

            // Sync with PlayerXp model for existing pages
            PlayerXp::updateOrCreate(
                ['character_name' => $charName],
                [
                    'user_id' => $user->id,
                    'player_name' => $playerName,
                    'status' => match ($status) {
                        'INACTIVE' => 'inactive',
                        'TRAVELER' => 'traveler',
                        default => 'active',
                    },
                    'rank' => $rankStr,
                    'level' => max(1, $level),
                    'sessions_count' => (int)($row['Görev'] ?? 0),
                    'rank_xp' => (int)$rankFloorXp,
                    'mission_xp' => (int)$questXp,
                    'report_xp' => (int)$reportXp,
                    'bailiff_xp' => (int)$bailiffXp,
                    'extra_xp' => (int)$exCharXp,
                    'bonus_xp' => (int)$wikiXp,
                    'total_xp' => (int)$totalXp,
                    'allowance_sp' => (int)$allowanceSp,
                ]
            );
        }

        // 5. Process Graveyard Characters from Sütun P-V (İsim_2, Oyuncu_2, etc.)
        foreach ($trackerData as $row) {
            $deadCharName = '';
            $questXp = 0.0;
            foreach ($row as $k => $v) {
                if (str_contains($k, 'sim_2')) {
                    $deadCharName = trim((string)$v);
                }
                if (str_contains($k, 'rev_3')) {
                    $questXp = (float)$v;
                }
            }
            $deadPlayerName = trim((string)($row['Oyuncu_2'] ?? ''));

            if ($deadCharName === '' || $deadCharName === 'İsim' || str_contains($deadCharName, 'sim') || $deadPlayerName === '' || $deadPlayerName === 'Oyuncu') {
                continue;
            }

            $user = $getOrCreateUser($deadPlayerName);
            $causeRaw = strtoupper(trim((string)($row['Sebep'] ?? 'KIA')));
            $cause = in_array($causeRaw, ['KIA', 'MIA']) ? $causeRaw : 'KIA';

            $questCount = (int)($row['Oyun'] ?? 0);
            $reportXp = (float)($row['Rapor_2'] ?? 0);
            $transferredXp = (float)($row['XP_2'] ?? 0);
            $totalDeathXp = $questXp + $reportXp;

            // Character entry for fallen hero
            $deadChar = Character::firstOrCreate(
                ['name' => $deadCharName],
                [
                    'user_id' => $user->id,
                    'character_type' => 'MAIN',
                    'status' => $cause,
                    'rank_id' => $rankAday?->id,
                    'current_level' => 1,
                    'allowance_sp' => 0.00,
                    'rank_floor_xp' => 0.00,
                    'quest_xp' => $questXp,
                    'report_xp' => $reportXp,
                    'bailiff_xp' => 0.00,
                    'ex_char_xp' => 0.00,
                    'wiki_xp' => 0.00,
                    'total_xp' => $totalDeathXp,
                ]
            );

            // Record in character_graveyard
            CharacterGraveyard::updateOrCreate(
                ['character_id' => $deadChar->id],
                [
                    'user_id' => $user->id,
                    'cause' => $cause,
                    'quest_count_at_death' => $questCount,
                    'quest_xp_at_death' => $questXp,
                    'report_xp_at_death' => $reportXp,
                    'transferred_xp' => $transferredXp,
                    'recorded_at' => now(),
                ]
            );

            // Sync with PlayerXp model for fallen section
            PlayerXp::updateOrCreate(
                ['character_name' => $deadCharName],
                [
                    'user_id' => $user->id,
                    'player_name' => $deadPlayerName,
                    'status' => 'fallen',
                    'rank' => 'Aday',
                    'level' => 1,
                    'sessions_count' => $questCount,
                    'rank_xp' => 0,
                    'mission_xp' => (int)$questXp,
                    'report_xp' => (int)$reportXp,
                    'bailiff_xp' => 0,
                    'extra_xp' => 0,
                    'bonus_xp' => 0,
                    'total_xp' => (int)$transferredXp,
                    'allowance_sp' => 0,
                    'reason' => $cause,
                ]
            );
        }
    }
}
