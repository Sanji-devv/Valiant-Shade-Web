<?php

namespace App\ValiantShade\Services;

use App\Models\Character;
use App\Models\PlayerXp;
use App\Models\Quest;
use App\Models\QuestParticipant;
use Carbon\Carbon;
use Database\Seeders\QuestReportsSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuestReportService
{
    /**
     * Storage path for quest reports JSON file.
     */
    protected function getStoragePath(): string
    {
        if (app()->runningUnitTests()) {
            return storage_path('app/quest_reports_testing.json');
        }

        return storage_path('app/quest_reports.json');
    }

    /**
     * Ensure the storage file is initialized with the 32 master sheet reports.
     */
    public function ensureInitialized(): void
    {
        $path = $this->getStoragePath();
        if (! file_exists($path)) {
            $this->resetToDefaults();
        }
    }

    /**
     * Reset quest reports dataset to the original 32 master reports.
     */
    public function resetToDefaults(): array
    {
        if (app()->runningUnitTests()) {
            $raw = $this->getRawReportsData();
            $path = $this->getStoragePath();
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($path, json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $raw;
        }

        try {
            $seeder = new QuestReportsSeeder;
            $seeder->run();
            $path = $this->getStoragePath();
            if (file_exists($path)) {
                $decoded = json_decode(file_get_contents($path), true);
                if (is_array($decoded) && ! empty($decoded)) {
                    return $decoded;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to raw data
        }

        $raw = $this->getRawReportsData();
        $path = $this->getStoragePath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $raw;
    }

    public function resetToExcelDefaults(): array
    {
        return $this->resetToDefaults();
    }

    /**
     * Get all quest reports from storage (with fallback).
     */
    public function getAllReportsData(): array
    {
        $this->ensureInitialized();
        $path = $this->getStoragePath();
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $decoded = json_decode($json, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded;
            }
        }

        return $this->getRawReportsData();
    }

    /**
     * Save reports array to storage file.
     */
    protected function saveReportsData(array $reports): void
    {
        $path = $this->getStoragePath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $json = json_encode(array_values($reports), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Görev raporları dosyası kaydedilemedi.');
        }
    }

    /**
     * Import master-sheet rows into the quest tables and the report archive.
     *
     * @return array{imported: int, skipped: int}
     */
    public function importReports(array $rows): array
    {
        if (! Schema::hasTable('quests') || ! Schema::hasTable('quest_participants')) {
            throw new \RuntimeException('Görev raporları tabloları bulunamadı. Önce veritabanı migration işlemlerini çalıştırın.');
        }

        $allReports = $this->getAllReportsData();
        $canonicalKeys = [];
        $nextId = 1;
        foreach ($allReports as $report) {
            $key = $this->reportIdentity((string) ($report['title'] ?? ''), (string) ($report['date'] ?? ''));
            if ($key !== '|') {
                $canonicalKeys[$key] = (int) ($report['id'] ?? 0);
            }
            $nextId = max($nextId, ((int) ($report['id'] ?? 0)) + 1);
        }

        $existingQuestKeys = [];
        foreach (Quest::query()->get(['title', 'quest_date']) as $quest) {
            $questDate = $quest->quest_date instanceof \DateTimeInterface
                ? $quest->quest_date->format('Y-m-d')
                : (string) ($quest->quest_date ?? '');
            $existingQuestKeys[$this->reportIdentity($quest->title, $questDate)] = true;
        }

        $characterNames = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $characterNames[] = trim((string) ($row['GM'] ?? ''));
            $characterNames[] = trim((string) ($row['Raporlayan'] ?? ''));
            foreach ($this->importParticipantNames($row) as $name) {
                $characterNames[] = $name;
            }
        }
        $characters = Character::whereIn('name', array_values(array_unique(array_filter($characterNames))))
            ->get()
            ->keyBy('name');

        $imported = 0;
        $skipped = 0;
        $newReports = [];

        DB::transaction(function () use (
            $rows,
            &$allReports,
            &$canonicalKeys,
            &$existingQuestKeys,
            &$nextId,
            &$imported,
            &$skipped,
            &$newReports,
            $characters
        ): void {
            foreach ($rows as $row) {
                if (! is_array($row) || trim((string) ($row['Görev adı'] ?? '')) === '') {
                    $skipped++;

                    continue;
                }

                $title = trim((string) $row['Görev adı']);
                [$displayDate, $databaseDate] = $this->parseImportDate($row['Tarih'] ?? '');
                $identityDate = $databaseDate ?? trim((string) ($row['Tarih'] ?? ''));
                $key = $this->reportIdentity($title, $identityDate);

                $mqValue = trim((string) ($row['MQ'] ?? ''));
                $isEpic = in_array($mqValue, ['✓✓', '🗸🗸'], true);
                $isMain = $isEpic || in_array(mb_strtolower($mqValue), ['✓', '🗸', '1', 'yes', 'true'], true);
                $mq = $isEpic ? 'epic' : ($isMain ? 'main' : 'none');

                $depoValue = $row['column_F'] ?? null;
                $depoStatus = $depoValue === null
                    ? (float) ($row['Depo'] ?? 0) === 0.0
                    : in_array(mb_strtolower(trim((string) $depoValue)), ['✓', '🗸', '1', 'true', 'yes'], true);
                $reporter = trim((string) ($row['Raporlayan'] ?? '')) ?: 'Tolrun';
                $otherPlayers = $this->importParticipantNames($row);
                $level = max(1, (int) ($row['Level'] ?? 1));
                $totalXp = is_numeric($row['XP'] ?? null) ? (float) $row['XP'] : 0.0;
                $guildCut = is_numeric($row['Lonca'] ?? null) ? (float) $row['Lonca'] : 0.0;
                $depoCut = is_numeric($row['Depo'] ?? null) ? (float) $row['Depo'] : 0.0;
                $raporCut = is_numeric($row['Rapor'] ?? null) ? (float) $row['Rapor'] : 0.0;
                $extraReward = is_numeric($row['Ek'] ?? null) ? (float) $row['Ek'] : 0.0;
                $playerCount = is_numeric($row['Oyuncu'] ?? null)
                    ? max(1, (int) $row['Oyuncu'])
                    : 1 + count($otherPlayers);
                $avgReward = is_numeric($row['Ortalama'] ?? null)
                    ? (float) $row['Ortalama']
                    : round(($totalXp - $guildCut - $depoCut - $raporCut + $extraReward) / $playerCount, 2);

                $computed = $this->calculateReportValues([
                    'mq' => $mq,
                    'level' => $level,
                    'total_xp' => $totalXp,
                    'depo_status' => $depoStatus,
                    'rapor_cut' => $raporCut,
                    'extra_reward' => $extraReward,
                    'reporter' => $reporter,
                    'other_players' => $otherPlayers,
                    'player_count' => $playerCount,
                ]);
                $computed['guild_cut'] = $guildCut;
                $computed['depo_cut'] = $depoCut;
                $computed['avg_reward'] = $avgReward;
                $computed['player_count'] = $playerCount;
                foreach ($computed['party'] as &$member) {
                    $member['xp'] = (int) round($avgReward);
                }
                unset($member);

                $report = array_merge([
                    'id' => $canonicalKeys[$key] ?? $nextId++,
                    'title' => $title,
                    'date' => $displayDate,
                    'status' => $depoStatus ? 'success' : 'failed',
                    'gm' => trim((string) ($row['GM'] ?? '')) ?: 'Shalvaz',
                    'location' => 'Siwion',
                    'enemies' => 'Canavarlar ve Eşkıyalar',
                    'report_summary' => "{$title} görevi tamamlandı. Toplam {$totalXp} XP dağıtıldı.",
                    'full_report' => "{$title} görevi için kaynak rapor verileri içe aktarıldı.",
                ], $computed);

                if (! array_key_exists($key, $canonicalKeys)) {
                    $newReports[] = $report;
                    $canonicalKeys[$key] = $report['id'];
                }

                if (isset($existingQuestKeys[$key])) {
                    $skipped++;

                    continue;
                }

                $gmName = trim((string) ($row['GM'] ?? ''));
                $gmCharacter = $characters->get($gmName);
                $reporterCharacter = $characters->get($reporter);
                $quest = Quest::create([
                    'title' => $title,
                    'quest_date' => $databaseDate,
                    'is_main_quest' => $isMain,
                    'level' => $level,
                    'base_xp' => $totalXp,
                    'depo_fee_xp' => $depoCut,
                    'guild_cut_xp' => $guildCut,
                    'report_reward_xp' => $raporCut,
                    'bonus_pool_xp' => $extraReward,
                    'net_pool_xp' => $totalXp - $guildCut - $depoCut - $raporCut + $extraReward,
                    'per_player_xp' => $avgReward,
                    'gm_character_id' => $gmCharacter?->id,
                    'reporter_character_id' => $reporterCharacter?->id,
                    'status' => 'APPROVED',
                    'report_summary' => $report['report_summary'],
                    'full_report' => $report['full_report'],
                ]);

                $participants = [];
                foreach (array_unique(array_merge([$reporter], $otherPlayers)) as $name) {
                    $character = $characters->get($name);
                    if (! $character) {
                        continue;
                    }
                    $isReporter = $name === $reporter;
                    $participants[$character->id] = [
                        'quest_id' => $quest->id,
                        'character_id' => $character->id,
                        'earned_quest_xp' => $avgReward,
                        'earned_report_xp' => $isReporter ? $raporCut : 0,
                        'is_kia' => false,
                        'is_mia' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if ($participants) {
                    QuestParticipant::insert(array_values($participants));
                }

                $existingQuestKeys[$key] = true;
                $imported++;
            }

            if ($newReports) {
                $allReports = array_merge($newReports, $allReports);
                $this->saveReportsData($allReports);
            }
        });

        if ($imported === 0 && $skipped === 0) {
            throw new \InvalidArgumentException('JSON dosyasında içe aktarılabilir görev raporu bulunamadı.');
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * @return array<int, string>
     */
    private function importParticipantNames(array $row): array
    {
        $names = $row['Diğer Oyuncular'] ?? [];
        if (is_string($names)) {
            $names = explode(',', $names);
        }
        if (! is_array($names)) {
            $names = [];
        }

        foreach ([
            'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW',
            'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE',
        ] as $column) {
            $key = 'column_'.$column;
            if (isset($row[$key]) && ! is_array($row[$key])) {
                $names[] = $row[$key];
            }
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($name) => trim((string) $name),
            $names
        ), static fn ($name) => $name !== '' && ! in_array($name, ['-', 'Yok'], true))));
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function parseImportDate(mixed $date): array
    {
        $rawDate = trim((string) $date);
        if ($rawDate === '') {
            return ['', null];
        }

        try {
            $parsed = Carbon::parse($rawDate);

            return [$parsed->format('d/m/Y'), $parsed->toDateString()];
        } catch (\Exception) {
            return [$rawDate, null];
        }
    }

    private function reportIdentity(string $title, string $date): string
    {
        $date = trim($date);
        if ($date !== '') {
            try {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
                    $date = Carbon::createFromFormat('!d/m/Y', "{$matches[1]}/{$matches[2]}/{$matches[3]}")->toDateString();
                } else {
                    $date = Carbon::parse($date)->toDateString();
                }
            } catch (\Exception) {
                $date = mb_strtolower($date);
            }
        }

        return mb_strtolower(trim($title)).'|'.$date;
    }

    /**
     * Calculate all dynamic values according to the master sheet formulas:
     * - XP: =(Level*250+500)*IF(MQ=="✓✓",3,1)*COUNTA(Level)
     * - Lonca: =XP*0.05
     * - Depo: =IF(DepoStatus=="✗",XP*0.2,0)
     * - Ortalama: =(XP-Lonca-Depo-Rapor+Ek)/Oyuncu
     * - Oyuncu: =COUNTA(Raporlayan + Diger Oyuncular)
     */
    public function calculateReportValues(array $data): array
    {
        $level = max((int) ($data['level'] ?? 1), 1);

        // Normalize MQ
        $mqInput = (string) ($data['mq'] ?? $data['tier_type'] ?? 'none');
        $isEpic = ($mqInput === 'epic' || $mqInput === '✓✓' || $mqInput === '🗸🗸');
        $isMain = ($mqInput === 'main' || $mqInput === '✓' || $mqInput === '🗸' || ! empty($data['is_main']));

        if ($isEpic) {
            $mq = 'epic';
            $mqMarker = '✓✓';
            $multiplier = 3;
            $tierType = 'epic';
        } elseif ($isMain) {
            $mq = 'main';
            $mqMarker = '✓';
            $multiplier = 1;
            $tierType = 'main';
        } else {
            $mq = 'none';
            $mqMarker = '✗';
            $multiplier = 1;
            $tierType = 'side';
        }

        // XP Formula: =(D2*250+500)*IF(C2="🗸🗸",3,1)*COUNTA(D2)
        $totalXp = ($level * 250 + 500) * $multiplier;
        if (isset($data['total_xp']) && is_numeric($data['total_xp']) && (int) $data['total_xp'] > 0 && empty($data['auto_calc_xp'])) {
            // Allow manual override only if explicitly specified
            $totalXp = (int) $data['total_xp'];
        }

        // Lonca Formula: =E2*0.05
        $guildCut = round($totalXp * 0.05, 2);

        // Depo Status (Column F): 🗸 or ✗
        // Depo Formula: =IF(F2="✗",E2*0.2,0)
        $depoStatus = true;
        if (isset($data['depo_status'])) {
            $depoStatus = filter_var($data['depo_status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($depoStatus === null) {
                $depoStatus = ($data['depo_status'] === '🗸' || $data['depo_status'] === '1' || $data['depo_status'] === 'true' || $data['depo_status'] === true || $data['depo_status'] === 'success');
            }
        } elseif (isset($data['status'])) {
            $depoStatus = ($data['status'] === 'success');
        } elseif (isset($data['depot_cut'])) {
            $depoStatus = ((float) $data['depot_cut'] == 0);
        }

        $depoCut = $depoStatus ? 0.0 : round($totalXp * 0.20, 2);

        // Rapor (Column I): Fix XP * 0.10 (Master Sheet Column I formula: =E2*0.1)
        $raporCut = isset($data['rapor_cut']) && is_numeric($data['rapor_cut'])
            ? (float) $data['rapor_cut']
            : (isset($data['vault_cut']) && is_numeric($data['vault_cut']) ? (float) $data['vault_cut'] : round($totalXp * 0.10, 2));
        $extraReward = (float) ($data['extra_reward'] ?? 0);

        // Party & Players
        $reporter = trim((string) ($data['reporter'] ?? 'Tolrun'));
        if (empty($reporter)) {
            $reporter = 'Tolrun';
        }

        $otherPlayers = $data['other_players'] ?? [];
        if (! is_array($otherPlayers)) {
            if (is_string($otherPlayers)) {
                $otherPlayers = array_filter(array_map('trim', explode(',', $otherPlayers)));
            } else {
                $otherPlayers = [];
            }
        }
        $otherPlayers = array_values(array_filter($otherPlayers));

        // Total Players Formula: =COUNTA(AN2:BG2) (Raporlayan + Diğer Oyuncular)
        $playerCount = 1 + count($otherPlayers);
        if (isset($data['player_count']) && (int) $data['player_count'] > $playerCount) {
            $playerCount = (int) $data['player_count'];
        }

        // Ortalama Formula: =(E2-G2-H2-I2+J2)/AL2
        $netPool = $totalXp - $guildCut - $depoCut - $raporCut + $extraReward;
        $avgReward = round($netPool / max($playerCount, 1), 2);

        // Build party list for dossier modal
        $party = [];
        $party[] = [
            'name' => $reporter,
            'role' => 'Reporter',
            'class' => $data['reporter_class'] ?? 'Fighter',
            'level' => $level,
            'xp' => (int) round($avgReward),
        ];

        foreach ($otherPlayers as $playerName) {
            $party[] = [
                'name' => $playerName,
                'role' => 'Member',
                'class' => 'Adventurer',
                'level' => $level,
                'xp' => (int) round($avgReward),
            ];
        }

        return [
            'level' => $level,
            'mq' => $mq,
            'mq_marker' => $mqMarker,
            'is_main' => ($mq !== 'none'),
            'tier_type' => $tierType,
            'total_xp' => $totalXp,
            'depo_status' => $depoStatus,
            'guild_cut' => $guildCut,
            'depo_cut' => $depoCut,
            'rapor_cut' => $raporCut,
            'vault_cut' => $raporCut,
            'extra_reward' => $extraReward,
            'player_count' => $playerCount,
            'avg_reward' => $avgReward,
            'reporter' => $reporter,
            'other_players' => $otherPlayers,
            'party' => $party,
        ];
    }

    /**
     * Store a newly created quest report.
     */
    public function createReport(array $data): array
    {
        $all = $this->getAllReportsData();
        $nextId = count($all) > 0 ? max(array_column($all, 'id')) + 1 : 1;

        $computed = $this->calculateReportValues($data);

        $rawDate = trim($data['date'] ?? date('d/m/Y'));
        try {
            if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $rawDate, $m)) {
                $parsedDate = Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1]);
            } else {
                $parsedDate = Carbon::parse($rawDate);
            }
            $displayDate = $parsedDate->format('d/m/Y');
            $dbQuestDate = $parsedDate->toDateString();
        } catch (\Throwable $e) {
            $displayDate = $rawDate;
            $dbQuestDate = now()->toDateString();
        }

        $newReport = array_merge([
            'id' => $nextId,
            'title' => trim($data['title'] ?? 'Yeni Görev Raporu'),
            'date' => $displayDate,
            'status' => $data['status'] ?? 'success',
            'gm' => trim($data['gm'] ?? 'Shalvaz'),
            'location' => trim($data['location'] ?? 'Siwion Ticaret Bölgesi'),
            'enemies' => trim($data['enemies'] ?? 'Şehir Eşkıyaları'),
            'report_summary' => trim($data['report_summary'] ?? 'Görev başarıyla icra edildi.'),
            'full_report' => trim($data['full_report'] ?? ($data['report_summary'] ?? 'Görev detayları kaydedildi.')),
        ], $computed);

        // Prepend to list
        array_unshift($all, $newReport);
        $this->saveReportsData($all);

        // Sync to database Quests & QuestParticipants if available
        try {
            if (Schema::hasTable('quests')) {
                $gmChar = Character::where('name', $newReport['gm'])->first();
                $reporterChar = Character::where('name', $newReport['reporter'])->first();

                $quest = Quest::create([
                    'title' => $newReport['title'],
                    'quest_date' => $dbQuestDate,
                    'is_main_quest' => ! empty($newReport['is_main']),
                    'level' => (int) $newReport['level'],
                    'base_xp' => (float) $newReport['total_xp'],
                    'depo_fee_xp' => (float) $newReport['depo_cut'],
                    'guild_cut_xp' => (float) $newReport['guild_cut'],
                    'report_reward_xp' => (float) $newReport['rapor_cut'],
                    'bonus_pool_xp' => (float) $newReport['extra_reward'],
                    'net_pool_xp' => (float) ($newReport['total_xp'] - $newReport['guild_cut'] - $newReport['depo_cut'] - $newReport['rapor_cut'] + $newReport['extra_reward']),
                    'per_player_xp' => (float) $newReport['avg_reward'],
                    'gm_character_id' => $gmChar?->id,
                    'reporter_character_id' => $reporterChar?->id,
                    'status' => 'APPROVED',
                ]);

                if ($reporterChar && Schema::hasTable('quest_participants')) {
                    QuestParticipant::create([
                        'quest_id' => $quest->id,
                        'character_id' => $reporterChar->id,
                        'earned_quest_xp' => (float) $newReport['avg_reward'],
                        'earned_report_xp' => (float) $newReport['rapor_cut'],
                        'is_kia' => false,
                        'is_mia' => false,
                    ]);
                }

                if (Schema::hasTable('quest_participants')) {
                    foreach ($newReport['other_players'] ?? [] as $pName) {
                        $pChar = Character::where('name', $pName)->first();
                        if ($pChar) {
                            QuestParticipant::create([
                                'quest_id' => $quest->id,
                                'character_id' => $pChar->id,
                                'earned_quest_xp' => (float) $newReport['avg_reward'],
                                'earned_report_xp' => 0.00,
                                'is_kia' => false,
                                'is_mia' => false,
                            ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // DB sync is best-effort (e.g. during test sqlite in-memory without migrations)
        }

        return $newReport;
    }

    /**
     * Update an existing quest report.
     */
    public function updateReport(int|string $id, array $data): ?array
    {
        $all = $this->getAllReportsData();
        $id = (int) $id;
        $foundIndex = -1;

        foreach ($all as $idx => $r) {
            if ((int) $r['id'] === $id) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === -1) {
            return null;
        }

        $existing = $all[$foundIndex];
        $computed = $this->calculateReportValues(array_merge($existing, $data));

        $rawDate = trim($data['date'] ?? $existing['date']);
        try {
            if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $rawDate, $m)) {
                $parsedDate = Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1]);
            } else {
                $parsedDate = Carbon::parse($rawDate);
            }
            $displayDate = $parsedDate->format('d/m/Y');
            $dbQuestDate = $parsedDate->toDateString();
        } catch (\Throwable $e) {
            $displayDate = $rawDate;
            $dbQuestDate = now()->toDateString();
        }

        $updated = array_merge($existing, [
            'title' => trim($data['title'] ?? $existing['title']),
            'date' => $displayDate,
            'status' => $data['status'] ?? ($existing['status'] ?? 'success'),
            'gm' => trim($data['gm'] ?? ($existing['gm'] ?? 'Shalvaz')),
            'location' => trim($data['location'] ?? ($existing['location'] ?? 'Siwion')),
            'enemies' => trim($data['enemies'] ?? ($existing['enemies'] ?? 'Düşmanlar')),
            'report_summary' => trim($data['report_summary'] ?? ($existing['report_summary'] ?? '')),
            'full_report' => trim($data['full_report'] ?? ($existing['full_report'] ?? '')),
        ], $computed);

        $all[$foundIndex] = $updated;
        $this->saveReportsData($all);

        // Update database quest if present
        try {
            if (Schema::hasTable('quests')) {
                $qRecord = Quest::where('title', $existing['title'])->first();
                if ($qRecord) {
                    $qRecord->update([
                        'title' => $updated['title'],
                        'quest_date' => $dbQuestDate,
                        'level' => (int) $updated['level'],
                        'base_xp' => (float) $updated['total_xp'],
                        'depo_fee_xp' => (float) $updated['depo_cut'],
                        'guild_cut_xp' => (float) $updated['guild_cut'],
                        'report_reward_xp' => (float) $updated['rapor_cut'],
                        'bonus_pool_xp' => (float) $updated['extra_reward'],
                        'per_player_xp' => (float) $updated['avg_reward'],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Silently fallback
        }

        return $updated;
    }

    /**
     * Delete a quest report by ID.
     */
    public function deleteReport(int|string $id): bool
    {
        $all = $this->getAllReportsData();
        $id = (int) $id;
        $originalCount = count($all);

        $toDelete = collect($all)->firstWhere('id', $id);
        $filtered = array_filter($all, fn ($r) => (int) $r['id'] !== $id);

        if (count($filtered) !== $originalCount) {
            $this->saveReportsData($filtered);

            try {
                if ($toDelete && Schema::hasTable('quests')) {
                    $qRecord = Quest::where('title', $toDelete['title'])->first();
                    if ($qRecord) {
                        if (Schema::hasTable('quest_participants')) {
                            QuestParticipant::where('quest_id', $qRecord->id)->delete();
                        }
                        $qRecord->delete();
                    }
                }
            } catch (\Throwable $e) {
                // Silently fallback
            }

            return true;
        }

        return false;
    }

    /**
     * Distribute average XP from a report to Player XP Tracker & Character models.
     */
    public function distributeXpToPlayers(int|string $id): array
    {
        $report = $this->getReportById($id);
        if (! $report) {
            throw new \InvalidArgumentException("Görev raporu (#{$id}) bulunamadı.");
        }

        $awarded = [];
        $xpPerPlayer = (int) round($report['avg_reward']);
        $raporCut = (float) ($report['rapor_cut'] ?? $report['vault_cut'] ?? 0);
        $reporter = $report['reporter'];
        $allNames = array_unique(array_merge([$reporter], $report['other_players'] ?? []));

        try {
            foreach ($allNames as $name) {
                $isReporter = ($name === $reporter);
                $awardedReportXp = ($isReporter && $raporCut > 0) ? (int) round($raporCut) : 0;
                $playerXpUpdated = false;
                $charUpdated = false;

                // 1. Update PlayerXp model (XP Tracker)
                $adv = PlayerXp::where('character_name', $name)
                    ->orWhere('character_name', 'LIKE', "%{$name}%")
                    ->first();

                if ($adv) {
                    $adv->mission_xp += $xpPerPlayer;
                    if ($awardedReportXp > 0) {
                        $adv->report_xp = ($adv->report_xp ?? 0) + $awardedReportXp;
                    }
                    $adv->sessions_count += 1;
                    $adv->total_xp = $adv->calculateComputedTotalXp();
                    $adv->level = PlayerXp::calculateLevelFromXp($adv->total_xp);

                    $existingNotes = $adv->notes ? $adv->notes."\n" : '';
                    $reportXpNote = $awardedReportXp > 0 ? " (+{$awardedReportXp} Rapor XP)" : '';
                    $adv->notes = $existingNotes.'['.now()->format('Y-m-d H:i')."] +{$xpPerPlayer} Sefer XP{$reportXpNote} (Görev Raporu #{$report['id']}: {$report['title']})";
                    $adv->save();
                    $playerXpUpdated = true;
                }

                // 2. Update Character model (Adventurer Info & Database)
                $char = Character::where('name', $name)
                    ->orWhere('name', 'LIKE', "%{$name}%")
                    ->first();

                if ($char) {
                    $char->quest_xp = ($char->quest_xp ?? 0) + $xpPerPlayer;
                    if ($awardedReportXp > 0) {
                        $char->report_xp = ($char->report_xp ?? 0) + $awardedReportXp;
                    }
                    $char->total_xp = round(
                        (float) ($char->rank_floor_xp ?? 0) +
                        (float) ($char->quest_xp ?? 0) +
                        (float) ($char->report_xp ?? 0) +
                        (float) ($char->bailiff_xp ?? 0) +
                        (float) ($char->ex_char_xp ?? 0) +
                        (float) ($char->wiki_xp ?? 0),
                        2
                    );
                    $char->current_level = PlayerXp::calculateLevelFromXp((int) $char->total_xp);
                    $char->save();
                    $charUpdated = true;
                }

                $awarded[] = [
                    'name' => $name,
                    'role' => $isReporter ? 'Reporter' : 'Member',
                    'awarded_quest_xp' => $xpPerPlayer,
                    'awarded_report_xp' => $awardedReportXp,
                    'total_awarded' => $xpPerPlayer + $awardedReportXp,
                    'player_xp_updated' => $playerXpUpdated,
                    'character_updated' => $charUpdated,
                ];
            }
        } catch (\Throwable $e) {
            // DB connection or query error fallback
        }

        return [
            'report_id' => $report['id'],
            'report_title' => $report['title'],
            'xp_per_player' => $xpPerPlayer,
            'players_awarded' => $awarded,
            'total_awarded_count' => count($awarded),
        ];
    }

    /**
     * Get all quest reports with optional filtering.
     */
    public function getReports(array $filters = []): Collection
    {
        $reports = collect($this->getAllReportsData());

        // Filter by Type (Tümü, Ana Görevler, Yan Görevler, Epik)
        if (! empty($filters['type']) && $filters['type'] !== 'all') {
            if ($filters['type'] === 'main') {
                $reports = $reports->filter(fn ($q) => ($q['mq'] ?? '') === 'main' || (($q['is_main'] ?? false) && ($q['mq'] ?? '') !== 'epic'));
            } elseif ($filters['type'] === 'side') {
                $reports = $reports->filter(fn ($q) => ($q['mq'] ?? '') === 'none' || ! ($q['is_main'] ?? false));
            } elseif ($filters['type'] === 'epic') {
                $reports = $reports->filter(fn ($q) => ($q['mq'] ?? '') === 'epic');
            }
        }

        // Filter by GM
        if (! empty($filters['gm']) && $filters['gm'] !== 'all') {
            $gm = mb_strtolower($filters['gm']);
            $reports = $reports->filter(fn ($q) => mb_strtolower($q['gm'] ?? '') === $gm);
        }

        // Filter by Level
        if (! empty($filters['level']) && $filters['level'] !== 'all') {
            $lvl = (int) $filters['level'];
            $reports = $reports->filter(fn ($q) => (int) ($q['level'] ?? 1) === $lvl);
        }

        // Search term
        if (! empty($filters['search'])) {
            $term = mb_strtolower($filters['search']);
            $reports = $reports->filter(function ($q) use ($term) {
                if (str_contains(mb_strtolower($q['title']), $term)) {
                    return true;
                }
                if (str_contains(mb_strtolower($q['gm'] ?? ''), $term)) {
                    return true;
                }
                if (str_contains(mb_strtolower($q['reporter'] ?? ''), $term)) {
                    return true;
                }
                if (str_contains(mb_strtolower($q['location'] ?? ''), $term)) {
                    return true;
                }
                if (str_contains(mb_strtolower($q['report_summary'] ?? ''), $term)) {
                    return true;
                }

                // Check other players
                foreach ($q['other_players'] ?? [] as $other) {
                    if (str_contains(mb_strtolower($other), $term)) {
                        return true;
                    }
                }
                foreach ($q['party'] ?? [] as $member) {
                    if (str_contains(mb_strtolower($member['name'] ?? ''), $term)) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $reports->values();
    }

    /**
     * Get a single report by ID.
     */
    public function getReportById(int|string $id): ?array
    {
        $id = (int) $id;

        return collect($this->getAllReportsData())->firstWhere('id', $id);
    }

    /**
     * Compute summary statistics for header cards.
     */
    public function getStatistics(): array
    {
        $all = collect($this->getAllReportsData());

        $totalQuests = $all->count();
        $epicQuests = $all->where('mq', 'epic')->count();
        $mainQuests = $all->filter(fn ($q) => ($q['mq'] ?? '') === 'main' || (($q['is_main'] ?? false) && ($q['mq'] ?? '') !== 'epic'))->count();
        $sideQuests = $all->filter(fn ($q) => ($q['mq'] ?? '') === 'none' || ! ($q['is_main'] ?? false))->count();

        $totalXp = (float) $all->sum('total_xp');
        $totalGuildCut = (float) $all->sum('guild_cut');
        $totalDepoCut = (float) $all->sum('depo_cut');
        $totalRaporCut = (float) $all->sum('rapor_cut');
        $totalExtraReward = (float) $all->sum('extra_reward');

        $levels = $all->pluck('level')->map(fn ($l) => (float) $l)->filter();
        $avgLevel = $levels->count() > 0 ? round($levels->avg(), 2) : 1.0;

        // GM Distribution
        $gmCounts = $all->groupBy('gm')->map->count()->sortDesc()->toArray();
        $topGm = key($gmCounts) ?? 'Shalvaz';

        return [
            'total_quests' => $totalQuests,
            'main_quests' => $mainQuests,
            'epic_quests' => $epicQuests,
            'side_quests' => $sideQuests,
            'total_xp' => $totalXp,
            'total_guild_cut' => $totalGuildCut,
            'total_depo_cut' => $totalDepoCut,
            'total_rapor_cut' => $totalRaporCut,
            'total_extra_reward' => $totalExtraReward,
            'avg_level' => $avgLevel,
            'top_gm' => $topGm,
            'gm_counts' => $gmCounts,
        ];
    }

    /**
     * Get distinct GM list.
     */
    public function getDistinctGms(): array
    {
        return collect($this->getAllReportsData())
            ->pluck('gm')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get list of known adventurer character names for autocomplete.
     */
    public function getAvailableAdventurers(): array
    {
        try {
            $dbNames = PlayerXp::orderBy('character_name')->pluck('character_name')->filter()->toArray();
            if (! empty($dbNames)) {
                return $dbNames;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        // Comprehensive default roster extracted from master sheet and Player XP tracker
        return [
            'Aelnor', 'Aelinor', 'Aeglish', 'Aeris', 'Aeswar', 'Afatsum', 'Aiden', 'Aki', 'Aktar Yağcıoğlu',
            'Al Di Meola', 'Alamir', 'Alarkyn', 'Alberto', 'Aldakoy', 'Aldebaran', 'Aleifkeid', 'Alfred',
            'Alia', 'Alice', 'Allerion', 'Altıgen', 'Amoril', 'Antigonus', 'Antonio', 'Apha', 'Archen',
            'Arelion', 'Arelion Vane', 'Argom', 'Argon Ranfer', 'Argus', 'Arin', 'Arkhan', 'Averon Felt',
            'Bart', 'Bruk', 'Buskall', 'Caselgo', 'Caspian', 'Darren', 'Demirpençe', 'Drednar', 'Elindel',
            'Ethelrinda', 'Fangor', 'Hall', 'Lagnard', 'Leothar', 'Lyra', 'Marcus', 'Nedasp', 'Nicholas',
            'Nyreen', 'Ogden', 'Ran', 'Richard', 'Rindark', "Rock'naar", 'Rufus', 'Samih', 'Shalvaz',
            'Shantar', 'Skadmir', 'Tamokul', 'Tolrun', 'Ugly', 'Ulrich', 'Vain', 'Valen', 'Veigar',
            'Viktor', 'Vostea', 'Will', 'Willhard',
        ];
    }

    /**
     * Raw master sheet data extracted directly from the user's Görev Raporları spreadsheet tab (32 rows).
     */
    public function getRawReportsData(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Kasadaki Para',
                'date' => '8/8',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 10.0,
                'vault_cut' => 10.0,
                'extra_reward' => 0.0,
                'avg_reward' => 175.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Tolrun',
                'other_players' => ['Allerion', 'Veigar', 'Shantar'],
                'location' => 'Siwion Ticaret Bölgesi',
                'enemies' => 'Şehir Hırsızları, Rüşvetçi Muhafızlar',
                'report_summary' => 'Lonca finans ambarına sızmaya çalışan yerel şebekenin kasayı soyma girişimi engellendi. Kaçırılmak üzere olan 10 SP lonca kasasına iade edildi.',
                'full_report' => 'Operasyon Özeti: Gece yarısı saatlerinde Siwion alt ticaret mahallesinde şüpheli hareketlilik raporlandı. Tolrun önderliğindeki ekip, eski lonca deposunun arka girişine kurulan pusuyu fark etti. Kısa bir arbedenin ardından hırsız çetesi etkisiz hale getirildi. Kasada bulunan değerli senetler ve 10 SP tutarındaki fon kurtarılarak lonca kasasına teslim edildi.',
                'party' => [
                    ['name' => 'Tolrun', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 176],
                    ['name' => 'Allerion', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 176],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 176],
                    ['name' => 'Shantar', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 176],
                ],
            ],
            [
                'id' => 2,
                'title' => 'Aile Yadigarlarının Peşinde',
                'date' => '15/8',
                'mq' => 'main',
                'mq_marker' => '✓',
                'is_main' => true,
                'tier_type' => 'main',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 0.0,
                'vault_cut' => 0.0,
                'extra_reward' => 0.0,
                'avg_reward' => 142.50,
                'player_count' => 5,
                'gm' => 'Vain',
                'reporter' => 'Leothar',
                'other_players' => ['Veigar', 'Darren', 'Willhard', 'Caselgo'],
                'location' => 'Kuzey Siwion Mahzenleri',
                'enemies' => 'Mezar Yağmacıları, Zombi Muhafızlar',
                'report_summary' => 'Aristokrat ailenin çalınan kadim hançeri ve mühür yüzüğü terk edilmiş mahzenlerden çıkarıldı.',
                'full_report' => "Görev Günlüğü: Kadim soylulardan Lord Cassian'ın atalarına ait yadigar sandığı, gizli bir tarikat tarafından yağmalanmıştı. Leothar, Darren, Veigar, Willhard ve Caselgo'dan oluşan beş kişilik keşif birliği mahzen tünellerine girdi. Çatışmada mezar hırsızları bertaraf edildi, yadigar başarıyla aileye teslim edildi.",
                'party' => [
                    ['name' => 'Leothar', 'role' => 'Reporter', 'class' => 'Paladin', 'level' => 1, 'xp' => 143],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 143],
                    ['name' => 'Darren', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 143],
                    ['name' => 'Willhard', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 143],
                    ['name' => 'Caselgo', 'role' => 'Member', 'class' => 'Bard', 'level' => 1, 'xp' => 143],
                ],
            ],
            [
                'id' => 3,
                'title' => 'Siwion Paladinleri Soruşturması',
                'date' => '17/8',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 0.0,
                'vault_cut' => 0.0,
                'extra_reward' => 0.0,
                'avg_reward' => 178.13,
                'player_count' => 4,
                'gm' => 'Bart',
                'reporter' => "Rock'naar",
                'other_players' => ['Rindark', 'Nyreen', 'Shalvaz'],
                'location' => 'Işık Tapınağı Surları',
                'enemies' => 'Sapkın Muhafızlar, Casuslar',
                'report_summary' => 'Paladin kışlasında çıkan yetki çatışması ve rüşvet iddiaları yerinde araştırıldı.',
                'full_report' => "Soruşturma Tutanağı: Tapınak muhafızları arasındaki huzursuzluk loncamıza iletildiğinde Rock'naar ve ekibi kışlaya intikal etti. Şüpheli mektuplar ele geçirildi ve tapınağın içine sızmış olan yabancı ajanlar deşifre edildi.",
                'party' => [
                    ['name' => "Rock'naar", 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 178],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 178],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 178],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 178],
                ],
            ],
            [
                'id' => 4,
                'title' => 'Harken Ayaklanması Pt-1',
                'date' => '22/8',
                'mq' => 'epic',
                'mq_marker' => '✓✓',
                'is_main' => true,
                'tier_type' => 'epic',
                'level' => 1,
                'total_xp' => 2250,
                'depo_status' => true,
                'guild_cut' => 112.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 0.0,
                'vault_cut' => 0.0,
                'extra_reward' => 0.0,
                'avg_reward' => 356.25,
                'player_count' => 6,
                'gm' => 'Bart',
                'reporter' => 'Averon Felt',
                'other_players' => ['Buskall', 'Elindel', 'Leothar', 'Nyreen', 'Ran'],
                'location' => 'Harken Vadisi Girişi',
                'enemies' => 'İsyancı Paralı Askerler, Kuşatma Birlikleri',
                'report_summary' => 'Harken Dükalığı genelinde başlayan isyanın ilk cephesi bastırıldı, köprü kontrol altına alındı.',
                'full_report' => 'Destansı Harekât Raporu (Bölüm 1): Harken bölgesindeki genel isyan bayrağı çekildiğinde Valiant Shade acil seferberlik ilan etti. Averon Felt kumandasındaki 6 kahraman vadi köprüsünü tutarak isyancı takviye birliklerini püskürttü.',
                'party' => [
                    ['name' => 'Averon Felt', 'role' => 'Reporter', 'class' => 'Paladin', 'level' => 1, 'xp' => 356],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 356],
                    ['name' => 'Elindel', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 356],
                    ['name' => 'Leothar', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 356],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 356],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 356],
                ],
            ],
            [
                'id' => 5,
                'title' => 'Harken Ayaklanması Pt-2',
                'date' => '22/8',
                'mq' => 'epic',
                'mq_marker' => '✓✓',
                'is_main' => true,
                'tier_type' => 'epic',
                'level' => 1,
                'total_xp' => 2250,
                'depo_status' => true,
                'guild_cut' => 112.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 0.0,
                'vault_cut' => 0.0,
                'extra_reward' => 0.0,
                'avg_reward' => 356.25,
                'player_count' => 6,
                'gm' => 'Shalvaz',
                'reporter' => 'Rindark',
                'other_players' => ['Rufus', 'Tamokul', 'Ugly', 'Veigar', 'Vostea'],
                'location' => 'Harken Kale Meydanı',
                'enemies' => 'İsyancı Süvariler, Ağır Zırhlı Şövalyeler',
                'report_summary' => 'Harken garnizonu kurtarıldı ve kale kapıları isyancı kuşatmasından temizlendi.',
                'full_report' => 'Destansı Harekât Raporu (Bölüm 2): İkinci tabur kale surlarına yönelik koçbaşı taarruzunu durdurmak üzere kalabalık isyancı kanadına girdi. Rindark, Rufus, Tamokul, Ugly, Veigar ve Vostea ortak manevrayla isyancı bayraktarını düşürdü.',
                'party' => [
                    ['name' => 'Rindark', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 356],
                    ['name' => 'Rufus', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 356],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 356],
                    ['name' => 'Ugly', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 356],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 356],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 356],
                ],
            ],
            [
                'id' => 6,
                'title' => 'Harken Ayaklanması Pt-3',
                'date' => '22/8',
                'mq' => 'epic',
                'mq_marker' => '✓✓',
                'is_main' => true,
                'tier_type' => 'epic',
                'level' => 1,
                'total_xp' => 2250,
                'depo_status' => true,
                'guild_cut' => 112.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 0.0,
                'vault_cut' => 0.0,
                'extra_reward' => 0.0,
                'avg_reward' => 356.25,
                'player_count' => 6,
                'gm' => 'Vain',
                'reporter' => 'Willhard',
                'other_players' => ['Allerion', 'Skadmir', "Rock'naar", 'Ogden', 'Shantar'],
                'location' => 'Igor Harken Sığınağı',
                'enemies' => 'İsyancı Elebaşı Igor Harken, Gölge Muhafızlar',
                'report_summary' => 'İsyanın beyni Igor Harken kıstırıldı ve teslim alındı. İsyanın arkasındaki finansal kaynaklar ele geçirildi.',
                'full_report' => 'Destansı Harekât Raporu (Bölüm 3): Willhard komutasındaki üçüncü darbe timi sığınağa baskın düzenledi. Igor Harken ağır yaralanarak canlı ele geçirildi ve yargılanmak üzere Siwion Lonca Zindanlarına sevk edildi.',
                'party' => [
                    ['name' => 'Willhard', 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 356],
                    ['name' => 'Allerion', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 356],
                    ['name' => 'Skadmir', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 356],
                    ['name' => "Rock'naar", 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 356],
                    ['name' => 'Ogden', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 356],
                    ['name' => 'Shantar', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 356],
                ],
            ],
            [
                'id' => 7,
                'title' => 'Temsilcinin Kolyesi',
                'date' => '27/8',
                'mq' => 'main',
                'mq_marker' => '✓',
                'is_main' => true,
                'tier_type' => 'main',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Bart',
                'reporter' => 'Elindel',
                'other_players' => ['Tamokul', 'Buskall', 'Bruk'],
                'location' => 'Büyükelçilik Konutu',
                'enemies' => 'Suikastçılar, Hırsız Loncası Casusları',
                'report_summary' => 'Kraliyet temsilcisinden çalınan büyü korumalı kolye kurtarıldı. Lonca kasasına 50 SP aktarıldı.',
                'full_report' => 'Görev Raporu: Diplomatik kriz yaratabilecek hırsızlık vakasında Elindel ve arkadaşları ipuçlarını takip ederek liman depolarındaki kaçakçı teknesine baskın yaptı. Kolye sağlam olarak geri getirildi.',
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 1, 'xp' => 166],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 166],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                    ['name' => 'Bruk', 'role' => 'Member', 'class' => 'Monk', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 8,
                'title' => 'Bernard Vefalı Tehlikede Mi?',
                'date' => '28/8',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Vain',
                'reporter' => 'Nicholas',
                'other_players' => ['Marcus', 'Tolrun', 'Veigar', 'Hall'],
                'location' => 'Bernard Vefalı Konağı',
                'enemies' => 'Şantajcılar, Zehirbazlar',
                'report_summary' => 'Şehir tüccarı Bernard Vefalı’ya kurulan zehirleme komplosu önlendi.',
                'full_report' => 'Operasyon Detayı: Nicholas liderliğindeki ekip tüccarın konağında nöbet tuttu. Aşçı kılığına giren suikastçı yemek servisi sırasında yakalandı.',
                'party' => [
                    ['name' => 'Nicholas', 'role' => 'Reporter', 'class' => 'Rogue', 'level' => 1, 'xp' => 133],
                    ['name' => 'Marcus', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 133],
                    ['name' => 'Tolrun', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 133],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 133],
                    ['name' => 'Hall', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 133],
                ],
            ],
            [
                'id' => 9,
                'title' => 'Igor Harken Duruşması',
                'date' => '29/8',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 0.0,
                'vault_cut' => 0.0,
                'extra_reward' => 0.0,
                'avg_reward' => 118.75,
                'player_count' => 6,
                'gm' => 'Bart',
                'reporter' => 'Hall',
                'other_players' => ['Rindark', 'Willhard', 'Ugly', 'Buskall', 'Bruk'],
                'location' => 'Yüksek Adalet Divanı',
                'enemies' => 'İsyancı İntikam Timi, Kaçırma Girişimi',
                'report_summary' => 'Mahkeme binası korundu, Igor Harken’ın infaz kararı onaylandı.',
                'full_report' => 'Duruşma Güvenlik Raporu: Mahkeme salonuna patlayıcılarla girmeye çalışan isyancı artıkları salon kapısında Hall ve ekibi tarafından durduruldu. Mahkeme güven içinde tamamlandı.',
                'party' => [
                    ['name' => 'Hall', 'role' => 'Reporter', 'class' => 'Cleric', 'level' => 1, 'xp' => 119],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 119],
                    ['name' => 'Willhard', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 119],
                    ['name' => 'Ugly', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 119],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 119],
                    ['name' => 'Bruk', 'role' => 'Member', 'class' => 'Monk', 'level' => 1, 'xp' => 119],
                ],
            ],
            [
                'id' => 10,
                'title' => 'Landon Çiftlikleri Gizemi',
                'date' => '29/8',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Tamokul',
                'other_players' => ['Aelinor', 'Vostea', 'Ran'],
                'location' => 'Landon Tarım Ovaları',
                'enemies' => 'Korkuluk Golemleri, Çılgın Simyacı',
                'report_summary' => 'Ekinleri yok eden ve çiftçileri korkutan canlandırılmış kuklalar yok edildi.',
                'full_report' => "Saha Raporu: Tamokul'un balyozu ekinliklerde hareket eden saman yaratıkları parçaladı. Gizli simya laboratuvarı mühürlendi ve 50 SP loncaya aktarıldı.",
                'party' => [
                    ['name' => 'Tamokul', 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 166],
                    ['name' => 'Aelinor', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 166],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 166],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 11,
                'title' => 'Igor Harken Suikastı Soruşturması',
                'date' => '30/8',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Vostea',
                'other_players' => ['Ran', 'Tamokul', 'Bart'],
                'location' => 'Siwion Yüksek Zindanı',
                'enemies' => 'Zindan Muhafızı Hainler, Suikastçılar',
                'report_summary' => 'Hücresinde öldürülen Igor Harken cinayetinin izleri takip edildi.',
                'full_report' => "İstihbarat Raporu: İnfazdan bir gece önce Igor Harken hücresinde ölü bulundu. Vostea'nın iz takibi cinayetin içeriden bir muhafız desteğiyle işlendiğini ortaya çıkardı.",
                'party' => [
                    ['name' => 'Vostea', 'role' => 'Reporter', 'class' => 'Druid', 'level' => 1, 'xp' => 166],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 166],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 166],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 12,
                'title' => 'Wellowburn Hanı Soruşturması Pt-1',
                'date' => '30/8',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => false, // ✗ Teslim Edilmedi -> 20% Depo Cut = 150!
                'guild_cut' => 37.5,
                'depo_cut' => 150.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 128.13,
                'player_count' => 4,
                'gm' => 'Bart',
                'reporter' => 'Ran',
                'other_players' => ['Vostea', 'Shalvaz', 'Hall'],
                'location' => 'Wellowburn Hanı',
                'enemies' => 'Karanlık Tüccarlar, Yasadışı Bahisçiler',
                'report_summary' => 'Handa yürütülen yasadışı bahis ve zehir ticareti basıldı. Depoya 150 malzeme aktarıldı, kasaya 50 SP kazandırıldı.',
                'full_report' => 'Baskın Raporu: Hanın gizli mahzeninde kurulan yasadışı bahis ve kaçak simya deposuna baskın yapıldı. %20 hammadde lonca deposuna çekildi.',
                'party' => [
                    ['name' => 'Ran', 'role' => 'Reporter', 'class' => 'Ranger', 'level' => 1, 'xp' => 128],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 128],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 128],
                    ['name' => 'Hall', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 128],
                ],
            ],
            [
                'id' => 13,
                'title' => 'Görev Raporu',
                'date' => '31/8',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Buskall',
                'other_players' => ['Samih', 'Veigar', 'Bart'],
                'location' => 'Siwion Batı Ormanları',
                'enemies' => 'Vahşi Yaban Domuzları, Orman Eşkıyaları',
                'report_summary' => 'Ticaret yolundaki eşkıya kampları temizlendi ve yol tüccarlar için tekrar güvenli kılındı.',
                'full_report' => 'Devriye Tutanağı: Buskall önderliğinde orman sınırında pusu kuran eşkıya grubu dağıtıldı, rehin alınan yük arabası kurtarıldı.',
                'party' => [
                    ['name' => 'Buskall', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                    ['name' => 'Samih', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 166],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 166],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 14,
                'title' => 'Ayaklanmadan Kalanlar',
                'date' => '1/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Shalvaz',
                'reporter' => 'Nicholas',
                'other_players' => ['Ran', 'Vostea', 'Will', 'Tolrun'],
                'location' => 'Harken Kırsalı',
                'enemies' => 'Kaçak İsyancılar, Yağmacılar',
                'report_summary' => 'İsyandan sonra köyleri basan firari çeteler yakalandı.',
                'full_report' => 'Saha Raporu: Firari askerlerin köylülerin erzak ambarlarına el koyduğu bilgisi üzerine Nicholas, Ran, Vostea, Will ve Tolrun köye yetişti. Çete etkisiz kılındı.',
                'party' => [
                    ['name' => 'Nicholas', 'role' => 'Reporter', 'class' => 'Rogue', 'level' => 1, 'xp' => 133],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 133],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 133],
                    ['name' => 'Will', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 133],
                    ['name' => 'Tolrun', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 133],
                ],
            ],
            [
                'id' => 15,
                'title' => 'Demircinin İstekleri',
                'date' => '1/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 220.83,
                'player_count' => 3,
                'gm' => 'Shalvaz',
                'reporter' => 'Tamokul',
                'other_players' => ['Veigar', "Rock'naar"],
                'location' => 'Demir Sırt Madenleri',
                'enemies' => 'Maden Trolleri, Kaya Yarasaları',
                'report_summary' => 'Lonca demircisi için nadir göktaşı madeni damarı bulundu ve güvenceye alındı.',
                'full_report' => "Maden Keşfi: Tamokul, Veigar ve Rock'naar madenin en derin galerilerine inerek göktaşı maden parçalarını çıkardı.",
                'party' => [
                    ['name' => 'Tamokul', 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 221],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 221],
                    ['name' => "Rock'naar", 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 221],
                ],
            ],
            [
                'id' => 16,
                'title' => 'Dolandırıcıların Peşinde',
                'date' => '1/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Shalvaz',
                'reporter' => 'Elindel',
                'other_players' => ['Tamokul', 'Buskall', 'Allerion', 'Ethelrinda'],
                'location' => 'Aşağı Pazar Yeri',
                'enemies' => 'Sahte İksir Tacirleri, Kumarbazlar',
                'report_summary' => 'Halkı sahte şifa iksirleriyle kandıran dolandırıcı çetesi çökertildi.',
                'full_report' => 'Şehir Güvenlik Raporu: Elindel ve ekibi sahte iksir atölyesini bastı, zararlı karışımlar imha edildi ve 50 SP fon tahsil edildi.',
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 1, 'xp' => 133],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 133],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 133],
                    ['name' => 'Allerion', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 133],
                    ['name' => 'Ethelrinda', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 133],
                ],
            ],
            [
                'id' => 17,
                'title' => 'Gnoll Tehlikesi',
                'date' => '1/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Vain',
                'reporter' => 'Leothar',
                'other_players' => ['Bart', 'Nyreen', 'Rindark', 'Willhard'],
                'location' => 'Kuru Vadi Geçidi',
                'enemies' => 'Gnoll Savaşçıları, Sırtlan Sürüleri',
                'report_summary' => 'Vadiyi kesen Gnoll sürüsü mağlup edildi, kervan yolu kurtarıldı.',
                'full_report' => 'Muharebe Raporu: Leothar ve Willhard ön safta kalkan duvarı örerek Gnoll taarruzunu kırdı. Nyreen ve Rindark kanatlardan vurarak sürüyü dağıttı.',
                'party' => [
                    ['name' => 'Leothar', 'role' => 'Reporter', 'class' => 'Paladin', 'level' => 1, 'xp' => 133],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 133],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 133],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 133],
                    ['name' => 'Willhard', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 133],
                ],
            ],
            [
                'id' => 18,
                'title' => 'Leyla <3 Sorin',
                'date' => '1/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Elindel',
                'other_players' => ['Nyreen', 'Tamokul', 'Rufus'],
                'location' => 'Gül Bahçeleri & Asil Malikane',
                'enemies' => 'Kıskanç Muhafızlar, Casus Kargalar',
                'report_summary' => 'İki asil ailenin genç aşıkları arasındaki gizli mektuplar ulaştırıldı.',
                'full_report' => "Gizli Görev Raporu: Siyasi gerginlik içerisindeki iki hanedanın gençleri arasındaki irtibat Elindel ve Nyreen'in büyüleriyle sağlandı.",
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 1, 'xp' => 166],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 166],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 166],
                    ['name' => 'Rufus', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 19,
                'title' => 'Liman Grevi',
                'date' => '1/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Bart',
                'reporter' => 'Aelinor',
                'other_players' => ['Shalvaz', 'Nyreen', 'Leothar', 'Allerion'],
                'location' => 'Siwion Ana Limanı',
                'enemies' => 'Kışkırtıcı Ajanlar, Liman Zorbaları',
                'report_summary' => 'Liman işçilerini kışkırtarak isyana sürüklemek isteyen ajanlar yakalandı.',
                'full_report' => 'Arabuluculuk Raporu: Aelinor işçilerle diplomasi yürütürken provokatörler Allorion ve Leothar tarafından yakalandı. Liman tekrar çalışmaya başladı.',
                'party' => [
                    ['name' => 'Aelinor', 'role' => 'Reporter', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 133],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 133],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 133],
                    ['name' => 'Leothar', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 133],
                    ['name' => 'Allerion', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 133],
                ],
            ],
            [
                'id' => 20,
                'title' => 'Şifalı Bitkiler',
                'date' => '1/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Elindel',
                'other_players' => ['Buskall', 'Veigar', 'Bart'],
                'location' => 'Sisli Bataklık',
                'enemies' => 'Zehirli Sarmaşıklar, Bataklık Yılanları',
                'report_summary' => 'Veba tehlikesine karşı gereken Gümüş Çiçeği kökleri toplandı.',
                'full_report' => 'Doğa Keşif Raporu: Bataklığın derinliklerinde yetişen nadir kökler Elindel ve Buskall tarafından toplandı. Lonca revirine şifa stoku sağlandı.',
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 1, 'xp' => 166],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 166],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 21,
                'title' => 'Sirkte Garip Gece',
                'date' => '1/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => "Rock'naar",
                'other_players' => ['Buskall', 'Veigar', 'Will'],
                'location' => 'Gezgin Sirk Çadırı',
                'enemies' => 'Büyülenmiş Vahşi Canavarlar, İllüzyonist',
                'report_summary' => 'Sirk çadırında hipnotize edilerek seyircilere saldırtılan aslanlar yatıştırıldı.',
                'full_report' => "Olay Müdahale Raporu: Rock'naar ve Will sahneye fırlayarak yırtıcıları sakinleştirdi. Seyircilerden hiçbiri zarar görmeden sirk müdürü tutuklandı.",
                'party' => [
                    ['name' => "Rock'naar", 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 166],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 166],
                    ['name' => 'Will', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 22,
                'title' => "Viktor'u Kurtarma",
                'date' => '1/9',
                'mq' => 'main',
                'mq_marker' => '✓',
                'is_main' => true,
                'tier_type' => 'main',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 0.0,
                'vault_cut' => 0.0,
                'extra_reward' => 0.0,
                'avg_reward' => 142.50,
                'player_count' => 5,
                'gm' => 'Bart',
                'reporter' => 'Ran',
                'other_players' => ['Vostea', 'Rindark', 'Marcus', 'Nicholas'],
                'location' => 'Haydut Kanyonu',
                'enemies' => 'Fidye Çetesi, Kurt Sürüleri',
                'report_summary' => 'Kaçırılan lonca irtibat subayı Viktor sağ salim kurtarıldı.',
                'full_report' => "Kurtarma Operasyonu: Kanyon içindeki mağarada esir tutulan Viktor, Ran ve Rindark'ın sızma taktiğiyle kurtarıldı. Çete reisi tutuklandı.",
                'party' => [
                    ['name' => 'Ran', 'role' => 'Reporter', 'class' => 'Ranger', 'level' => 1, 'xp' => 143],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 143],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 143],
                    ['name' => 'Marcus', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 143],
                    ['name' => 'Nicholas', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 143],
                ],
            ],
            [
                'id' => 23,
                'title' => 'Ebon Wings Geri Döndü!!',
                'date' => '2/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Nicholas',
                'other_players' => ['Samih', 'Ran', 'Vostea'],
                'location' => 'Kuzey Dağ Zirvesi',
                'enemies' => 'Dev Kuzgunlar, Gökyüzü Yağmacıları',
                'report_summary' => 'Dağ yollarındaki kuleleri taciz eden Ebon Wings kuşatıldı.',
                'full_report' => "Hava Taarruzu Raporu: Nicholas ve Samih gözetleme kulesine konuşlandı. Ran'in ok yağmuru yaratıkları geri çekilmeye zorladı.",
                'party' => [
                    ['name' => 'Nicholas', 'role' => 'Reporter', 'class' => 'Rogue', 'level' => 1, 'xp' => 166],
                    ['name' => 'Samih', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 166],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 166],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 24,
                'title' => 'Potion Bulma',
                'date' => '3/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Bart',
                'reporter' => 'Fangor',
                'other_players' => ['Richard', 'Shalvaz', 'Averon Felt'],
                'location' => 'Batık Kervan Kalıntıları',
                'enemies' => 'Bataklık Yaratıkları, Zehirli Sümüklüböcekler',
                'report_summary' => 'Bataklığa gömülen simya kervanının sağlam kalan iksir kasaları kurtarıldı.',
                'full_report' => 'İntikal Raporu: Fangor ve Averon Felt sular altındaki enkazı kaldırdı. 12 şişe şifa ve kudret iksiri lonca deposuna kazandırıldı.',
                'party' => [
                    ['name' => 'Fangor', 'role' => 'Reporter', 'class' => 'Warlock', 'level' => 1, 'xp' => 166],
                    ['name' => 'Richard', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 166],
                    ['name' => 'Averon Felt', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 166],
                ],
            ],
            [
                'id' => 25,
                'title' => 'Liman Çetelerini Soruşturma Sq-1',
                'date' => '5/9',
                'mq' => 'main',
                'mq_marker' => '✓',
                'is_main' => true,
                'tier_type' => 'main',
                'level' => 2,
                'total_xp' => 1000,
                'depo_status' => true,
                'guild_cut' => 50.0,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 150.0,
                'avg_reward' => 210.00,
                'player_count' => 5,
                'gm' => 'Bart',
                'reporter' => 'Elindel',
                'other_players' => ['Nedasp', 'Hall', 'Veigar', 'Bruk'],
                'location' => 'Gölge İskele Bölgesi',
                'enemies' => 'Gölge Çetesi Elebaşları, Bıçakçılar',
                'report_summary' => 'Limanı haraca bağlayan çetenin ana hücresi çökertildi. Ekstra 150 XP kazanıldı.',
                'full_report' => 'Seviye 2 Harekât Raporu: Elindel ve Nedasp çetenin kumarhanesine baskın yaptı. Çete kasasından 150 XP ekstra ganimet ve 50 SP lonca fonu ele geçirildi.',
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 2, 'xp' => 210],
                    ['name' => 'Nedasp', 'role' => 'Member', 'class' => 'Fighter', 'level' => 2, 'xp' => 210],
                    ['name' => 'Hall', 'role' => 'Member', 'class' => 'Cleric', 'level' => 2, 'xp' => 210],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 2, 'xp' => 210],
                    ['name' => 'Bruk', 'role' => 'Member', 'class' => 'Monk', 'level' => 2, 'xp' => 210],
                ],
            ],
            [
                'id' => 26,
                'title' => 'Liman Çetelerini Soruşturma Sq-2',
                'date' => '5/9',
                'mq' => 'main',
                'mq_marker' => '✓',
                'is_main' => true,
                'tier_type' => 'main',
                'level' => 2,
                'total_xp' => 1000,
                'depo_status' => true,
                'guild_cut' => 50.0,
                'depo_cut' => 0.0,
                'rapor_cut' => 0.0,
                'vault_cut' => 0.0,
                'extra_reward' => 150.0,
                'avg_reward' => 275.00,
                'player_count' => 4,
                'gm' => 'Vain',
                'reporter' => 'Ran',
                'other_players' => ['Ulrich', 'Vostea', 'Ugly'],
                'location' => 'Batık Gemi Tersanesi',
                'enemies' => 'Kaçak Silah Tüccarları, Çete Fedaileri',
                'report_summary' => 'Çetenin deniz yoluyla kaçmaya çalışan ikinci hücresi kuşatıldı ve teslim alındı.',
                'full_report' => 'Seviye 2 Harekât Raporu (Kısım 2): Ran, Ulrich, Vostea ve Ugly tersanede demirli fırkateyne gizlice sızdı. Kaçak silah ve erzak dolu kargo lonca kontrolüne geçti.',
                'party' => [
                    ['name' => 'Ran', 'role' => 'Reporter', 'class' => 'Ranger', 'level' => 2, 'xp' => 275],
                    ['name' => 'Ulrich', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 275],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 2, 'xp' => 275],
                    ['name' => 'Ugly', 'role' => 'Member', 'class' => 'Rogue', 'level' => 2, 'xp' => 275],
                ],
            ],
            [
                'id' => 27,
                'title' => 'Nonlethal Silah Yaptırma',
                'date' => '8/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Bart',
                'reporter' => 'Buskall',
                'other_players' => ['Bruk', 'Tamokul', 'Willhard', 'Aelinor'],
                'location' => 'Lonca Atölyeleri',
                'enemies' => 'Eğitim Golemleri, Test Mankeni Büyüleri',
                'report_summary' => 'Şehir içi operasyonlarda kan dökülmeden şüphelileri yakalamak için bayıltıcı teçhizat geliştirildi.',
                'full_report' => 'Atölye Test Raporu: Buskall ve ekibi prototip bayıltıcı sopaları test etti. 20 adet öldürücü olmayan teçhizat lonca envanterine girdi.',
                'party' => [
                    ['name' => 'Buskall', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 133],
                    ['name' => 'Bruk', 'role' => 'Member', 'class' => 'Monk', 'level' => 1, 'xp' => 133],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 133],
                    ['name' => 'Willhard', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 133],
                    ['name' => 'Aelinor', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 133],
                ],
            ],
            [
                'id' => 28,
                'title' => 'Kayıp Vatandaşlar Hakkında Pt-1',
                'date' => '11/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 75.0,
                'vault_cut' => 75.0,
                'extra_reward' => 0.0,
                'avg_reward' => 212.50,
                'player_count' => 3,
                'gm' => 'Bart',
                'reporter' => 'Leothar',
                'other_players' => ['Shalvaz', 'Nicholas'],
                'location' => 'Kanalizasyon Labirentleri',
                'enemies' => 'Kanal Ghoulları, Veba Sıçanları',
                'report_summary' => 'Şehir altından kaybolan 3 masum vatandaş kurtarıldı. Kasaya 75 SP bağışlandı.',
                'full_report' => 'Arama Kurtarma Raporu: Leothar kutsal ışığıyla kanalizasyonu aydınlatırken, Shalvaz ve Nicholas yaralı vatandaşlara ilk müdahaleyi yaptı.',
                'party' => [
                    ['name' => 'Leothar', 'role' => 'Reporter', 'class' => 'Paladin', 'level' => 1, 'xp' => 213],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 213],
                    ['name' => 'Nicholas', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 213],
                ],
            ],
            [
                'id' => 29,
                'title' => 'Haritacının Çalınan Malları',
                'date' => '12/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 110.42,
                'player_count' => 6,
                'gm' => 'Bart',
                'reporter' => 'Nedasp',
                'other_players' => ['Ran', 'Vostea', 'Ulrich', 'Vain', 'Leothar'],
                'location' => 'Doğu Patikası Harabe Kulesi',
                'enemies' => 'Haydut Okçuları, Yaban Tazıları',
                'report_summary' => 'Krallığın gizli sınır haritaları ve pusulaları haydut kampından geri alındı.',
                'full_report' => 'Geri Alma Harekâtı: Nedasp, Ran, Vostea, Ulrich, Vain ve Leothar haritacının kaçırılan belgelerini kuleye tırmanarak geri aldı.',
                'party' => [
                    ['name' => 'Nedasp', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 110],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 110],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 110],
                    ['name' => 'Ulrich', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 110],
                    ['name' => 'Vain', 'role' => 'Member', 'class' => 'Warlock', 'level' => 1, 'xp' => 110],
                    ['name' => 'Leothar', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 110],
                ],
            ],
            [
                'id' => 30,
                'title' => 'Liman Soruşturması Pt-1',
                'date' => '13/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 220.83,
                'player_count' => 3,
                'gm' => 'Shalvaz',
                'reporter' => 'Hall',
                'other_players' => ['Bart', 'Drednar'],
                'location' => 'Eski Gümrük Binası',
                'enemies' => 'Kaçak Mallar Muhafızları, Sahte Memurlar',
                'report_summary' => 'Gümrükte mühür bozup sahte vergi toplayan şebeke ortaya çıkarıldı.',
                'full_report' => 'Denetim Tutanağı: Hall, Bart ve Drednar gümrük ambarına baskın yaptı. Sahte defterler delil olarak mahkemeye sunuldu.',
                'party' => [
                    ['name' => 'Hall', 'role' => 'Reporter', 'class' => 'Cleric', 'level' => 1, 'xp' => 221],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 221],
                    ['name' => 'Drednar', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 221],
                ],
            ],
            [
                'id' => 31,
                'title' => 'Maaş Teslimatı',
                'date' => '14/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Drednar',
                'reporter' => 'Elindel',
                'other_players' => ['Veigar', 'Nicholas', 'Buskall', 'Leothar'],
                'location' => 'Sınır Muhafız Karakolu',
                'enemies' => 'Pusu Kuran Çeteler, Yaban Kurtları',
                'report_summary' => 'Sınır karakoluna gönderilen asker maaş sandığı salimen ulaştırıldı.',
                'full_report' => "Lojistik Güvenlik Raporu: Ağır zırhlı kervan arabası orman boğazında pusuya düşürüldü. Elindel'in alev büyüleri ve Buskall'ın kalkanı sayesinde karakola varıldı.",
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 1, 'xp' => 133],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 133],
                    ['name' => 'Nicholas', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 133],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 133],
                    ['name' => 'Leothar', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 133],
                ],
            ],
            [
                'id' => 32,
                'title' => 'Mahkum Transferi',
                'date' => '15/9',
                'mq' => 'none',
                'mq_marker' => '✗',
                'is_main' => false,
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'depo_status' => true,
                'guild_cut' => 37.5,
                'depo_cut' => 0.0,
                'rapor_cut' => 50.0,
                'vault_cut' => 50.0,
                'extra_reward' => 0.0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Tamokul',
                'other_players' => ['Rindark', 'Samih', 'Lagnard'],
                'location' => 'Siwion - Yüksek Hisar Yolu',
                'enemies' => 'Mahkum Kurtarma Çetesi, Zehirli Okçular',
                'report_summary' => 'Tehlikeli büyücü mahkum yüksek güvenlikli hisara teslim edildi.',
                'full_report' => 'Transfer Raporu: Tamokul, Rindark, Samih ve Lagnard mahkumu taşıyan demir kafesi korudu. Yolda düzenlenen kurtarma teşebbüsleri püskürtüldü.',
                'party' => [
                    ['name' => 'Tamokul', 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 166],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 166],
                    ['name' => 'Samih', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 166],
                    ['name' => 'Lagnard', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 166],
                ],
            ],
        ];
    }

    /**
     * Storage path for Guild Ranks & XP Pool allocation data.
     */
    protected function getGuildPoolStoragePath(): string
    {
        return storage_path('app/guild_ranks_pool.json');
    }

    /**
     * Get default Guild Ranks & XP Pool allocation dataset directly from Google Sheet screenshot (BU:CA columns).
     */
    public function getGuildPoolDefaultData(): array
    {
        return [
            'wiki_xp' => [
                'total' => 53800.0,
                'distributed' => 39547.0,
                'remaining' => 14253.0, // BW2 - BX2
            ],
            'guild_xp' => [
                'base' => 145000.0,
                'total' => 157150.0,
                'next_level_threshold' => 210000.0,
                'remaining' => 529,
                'level' => 12,
            ],
            'extra_fund' => 20570.0,
            'pool_total' => 2064199.61475, // BV5 Havuz (Formatted as 2,064,199.61)
            'pool_share_uzman' => 98295.21975, // BV6 Pay = BV5 / 21 = 98295.21975
            'ranks_total_spent' => 3439017.50, // CA7
            'grand_total_xp' => 3459587.50, // CA2 = CA7 + CA4
            'ranks' => [
                [
                    'rank' => 'Bailiff',
                    'capacity' => 168.78,
                    'current_count' => 0,
                    'capacity_xp' => 62860.00,
                    'current_xp' => 0.00,
                    'net_xp' => 0.00,
                    'spent_xp' => 174112.50,
                    'formula_desc' => '=COUNTA(A2:A)/9 | Kapasite XP = Lonca XP * %40 = 62,860.00 XP | Net Pay = 0.00 XP',
                ],
                [
                    'rank' => 'Uzman',
                    'capacity' => 168.78,
                    'current_count' => 21,
                    'capacity_xp' => 94290.00, // BW3 * 0.6 = 157150 * 0.6
                    'current_xp' => 94290.00,
                    'net_xp' => 98295.22, // Pay = 2064199.61 / 21
                    'spent_xp' => 2064199.61,
                    'formula_desc' => 'Kapasite XP = Lonca XP * %60 = 94,290.00 XP | Net Pay = Havuz / Uzman Sayısı = 98,295.22 XP',
                ],
                [
                    'rank' => 'Kıdemli',
                    'capacity' => 759.50,
                    'current_count' => 20,
                    'capacity_xp' => 401350.01,
                    'current_xp' => 401350.01,
                    'net_xp' => 20067.50, // CA11 / BW11 = 401350.01 / 20
                    'spent_xp' => 401350.01,
                    'formula_desc' => 'Kapasite = Üye / 2 = 759.50 | Net Pay = Harcanan XP / Olan = 401,350.01 / 20 = 20,067.50 XP',
                ],
                [
                    'rank' => 'Üye',
                    'capacity' => 1519.00,
                    'current_count' => 20,
                    'capacity_xp' => 3300.00,
                    'current_xp' => 3300.00,
                    'net_xp' => 1502.37, // CA12 / BW12 = 30047.37 / 20
                    'spent_xp' => 30047.37,
                    'formula_desc' => 'Kapasite = 1519 Sefer | Sabit Eşik = 3,300.00 XP | Net Pay = 30,047.37 / 20 = 1,502.37 XP',
                ],
                [
                    'rank' => 'Clerk',
                    'capacity' => 168.78,
                    'current_count' => 60,
                    'capacity_xp' => 15000.00,
                    'current_xp' => 15000.00,
                    'net_xp' => 3300.00, // CA13 / BW13 = 198000.00 / 60
                    'spent_xp' => 198000.00,
                    'formula_desc' => 'Kapasite = 168.78 | Sabit Fon = 15,000.00 XP | Net Pay = 198,000 / 60 = 3,300.00 XP',
                ],
                [
                    'rank' => 'Aday',
                    'capacity' => 2616.06,
                    'current_count' => 280,
                    'capacity_xp' => 1300.00,
                    'current_xp' => 1300.00,
                    'net_xp' => 0.00,
                    'spent_xp' => 0.00,
                    'formula_desc' => 'Kapasite = 2,616.06 | Eşik = 1,300.00 XP | Net Havuz Dağıtımı = 0.00 XP',
                ],
                [
                    'rank' => 'Inaktif / Gezgin',
                    'capacity' => 500.00,
                    'current_count' => 230,
                    'capacity_xp' => 571308.01,
                    'current_xp' => 571308.01,
                    'net_xp' => 2483.95,
                    'spent_xp' => 571308.01,
                    'formula_desc' => 'Olan = 230 Oyuncu | Harcanan XP = 571,308.01 XP | Kişi Başı Ortalama = 2,483.95 XP',
                ],
            ],
        ];
    }

    /**
     * Calculate all pool & ranks formulas dynamically.
     */
    public function calculateGuildPoolData(array $input): array
    {
        $defaults = $this->getGuildPoolDefaultData();

        $wikiTotal = (float) ($input['wiki_xp']['total'] ?? $defaults['wiki_xp']['total']);
        $wikiDist = (float) ($input['wiki_xp']['distributed'] ?? $defaults['wiki_xp']['distributed']);
        $wikiRem = round($wikiTotal - $wikiDist, 2);

        $guildTotal = (float) ($input['guild_xp']['total'] ?? $defaults['guild_xp']['total']);
        $guildBase = (float) ($input['guild_xp']['base'] ?? $defaults['guild_xp']['base']);
        $guildNext = (float) ($input['guild_xp']['next_level_threshold'] ?? $defaults['guild_xp']['next_level_threshold']);
        $guildLevel = (int) ($input['guild_xp']['level'] ?? $defaults['guild_xp']['level']);
        $guildRemaining = (int) ($input['guild_xp']['remaining'] ?? $defaults['guild_xp']['remaining']);

        $extraFund = (float) ($input['extra_fund'] ?? $defaults['extra_fund']);
        $poolTotal = (float) ($input['pool_total'] ?? $defaults['pool_total']);

        $ranks = $input['ranks'] ?? $defaults['ranks'];

        // Find Uzman count for Pay calculation: Pay = Havuz / Uzman Count
        $uzmanCount = 21;
        foreach ($ranks as $r) {
            if ($r['rank'] === 'Uzman') {
                $uzmanCount = max((int) ($r['current_count'] ?? 21), 1);
                break;
            }
        }
        $poolShareUzman = round($poolTotal / $uzmanCount, 5);

        // Calculate each rank's net and spent values according to master sheet formulas
        $totalSpent = 0.0;
        $totalCapacity = 0.0;
        $totalCount = 0;
        $totalCapacityXp = 0.0;
        $totalCurrentXp = 0.0;
        $totalNetXp = 0.0;

        foreach ($ranks as &$rk) {
            $count = (int) ($rk['current_count'] ?? 0);
            $spent = (float) ($rk['spent_xp'] ?? 0);

            if ($rk['rank'] === 'Uzman') {
                $rk['capacity'] = $rk['capacity'] ?? 168.78;
                $rk['capacity_xp'] = round($guildTotal * 0.60, 2); // =BW3*0.6
                $rk['current_xp'] = $rk['capacity_xp'];
                $rk['net_xp'] = round($poolShareUzman, 2); // =BV5/BW10
                $rk['formula_desc'] = 'Kapasite XP = Lonca XP * %60 ('.number_format($rk['capacity_xp'], 2).' XP) | Net Pay = Havuz / Uzman = '.number_format($rk['net_xp'], 2).' XP';
            } elseif ($rk['rank'] === 'Kıdemli') {
                $rk['capacity'] = $rk['capacity'] ?? 759.50;
                $rk['capacity_xp'] = $rk['capacity_xp'] ?? round($spent, 2);
                $rk['current_xp'] = $rk['current_xp'] ?? round($spent, 2);
                $rk['net_xp'] = $count > 0 ? round($spent / $count, 2) : 0.0; // =CA11/BW11
                $rk['formula_desc'] = 'Kapasite = Üye / 2 = 759.50 | Net Pay = '.number_format($spent, 2).' / '.$count.' = '.number_format($rk['net_xp'], 2).' XP';
            } elseif ($rk['rank'] === 'Üye') {
                $rk['capacity'] = $rk['capacity'] ?? 1519.00;
                $rk['capacity_xp'] = $rk['capacity_xp'] ?? 3300.00;
                $rk['current_xp'] = $rk['current_xp'] ?? 3300.00;
                $rk['net_xp'] = $count > 0 ? round($spent / $count, 2) : 0.0; // =CA12/BW12
                $rk['formula_desc'] = 'Kapasite = 1519 Sefer | Sabit Eşik = 3,300.00 XP | Net Pay = '.number_format($spent, 2).' / '.$count.' = '.number_format($rk['net_xp'], 2).' XP';
            } elseif ($rk['rank'] === 'Clerk') {
                $rk['capacity'] = $rk['capacity'] ?? 168.78;
                $rk['capacity_xp'] = $rk['capacity_xp'] ?? 15000.00;
                $rk['current_xp'] = $rk['current_xp'] ?? 15000.00;
                $rk['net_xp'] = $count > 0 ? round($spent / $count, 2) : 0.0; // =CA13/BW13
                $rk['formula_desc'] = 'Kapasite = 168.78 | Sabit Fon = 15,000.00 XP | Net Pay = '.number_format($spent, 2).' / '.$count.' = '.number_format($rk['net_xp'], 2).' XP';
            } elseif ($rk['rank'] === 'Aday') {
                $rk['capacity'] = $rk['capacity'] ?? 2616.06;
                $rk['capacity_xp'] = $rk['capacity_xp'] ?? 1300.00;
                $rk['current_xp'] = $rk['current_xp'] ?? 1300.00;
                $rk['net_xp'] = 0.0;
                $rk['formula_desc'] = 'Kapasite = 2,616.06 | Eşik = 1,300.00 XP | Havuz Dağıtımı = 0.00 XP';
            } elseif ($rk['rank'] === 'Bailiff') {
                $rk['capacity'] = $rk['capacity'] ?? 168.78;
                $rk['capacity_xp'] = round($guildTotal * 0.40, 2);
                $rk['current_xp'] = $count > 0 ? $rk['capacity_xp'] : 0.0;
                $rk['net_xp'] = $count > 0 ? round($spent / $count, 2) : 0.0;
                $rk['formula_desc'] = 'Kapasite = Sefer / 9 = 168.78 | Kapasite XP = Lonca XP * %40 = '.number_format($rk['capacity_xp'], 2).' XP';
            } elseif ($rk['rank'] === 'Inaktif / Gezgin') {
                $rk['capacity'] = $rk['capacity'] ?? 500.00;
                $rk['capacity_xp'] = $rk['capacity_xp'] ?? round($spent, 2);
                $rk['current_xp'] = $rk['current_xp'] ?? round($spent, 2);
                $rk['net_xp'] = $count > 0 ? round($spent / $count, 2) : 0.0;
                $rk['formula_desc'] = 'Inaktif ('.$count.' Oyuncu) | Harcanan XP = '.number_format($spent, 2).' XP | Ortalama = '.number_format($rk['net_xp'], 2).' XP';
            }

            $totalSpent += $spent;
            $totalCapacity += (float) ($rk['capacity'] ?? 0);
            $totalCount += $count;
            $totalCapacityXp += (float) ($rk['capacity_xp'] ?? 0);
            $totalCurrentXp += (float) ($rk['current_xp'] ?? 0);
            $totalNetXp += (float) ($rk['net_xp'] ?? 0);
        }

        $grandTotal = round($totalSpent + $extraFund, 2);

        return [
            'wiki_xp' => [
                'total' => $wikiTotal,
                'distributed' => $wikiDist,
                'remaining' => $wikiRem,
            ],
            'guild_xp' => [
                'base' => $guildBase,
                'total' => $guildTotal,
                'next_level_threshold' => $guildNext,
                'remaining' => $guildRemaining,
                'level' => $guildLevel,
            ],
            'extra_fund' => $extraFund,
            'pool_total' => $poolTotal,
            'pool_share_uzman' => $poolShareUzman,
            'ranks_total_spent' => round($totalSpent, 2),
            'grand_total_xp' => $grandTotal,
            'total_capacity' => round($totalCapacity, 2),
            'total_count' => $totalCount,
            'total_capacity_xp' => round($totalCapacityXp, 2),
            'total_current_xp' => round($totalCurrentXp, 2),
            'total_net_xp' => round($totalNetXp, 2),
            'ranks' => $ranks,
        ];
    }

    /**
     * Get active Guild Ranks & XP Pool allocation data.
     */
    public function getGuildPoolData(): array
    {
        $path = $this->getGuildPoolStoragePath();
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $decoded = json_decode($json, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $this->calculateGuildPoolData($decoded);
            }
        }

        return $this->calculateGuildPoolData($this->getGuildPoolDefaultData());
    }

    /**
     * Save updated Guild Pool data.
     */
    public function updateGuildPoolData(array $data): array
    {
        $calculated = $this->calculateGuildPoolData($data);
        $path = $this->getGuildPoolStoragePath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($calculated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $calculated;
    }

    /**
     * Reset Guild Pool data to default values from Google Sheet screenshot.
     */
    public function resetGuildPoolDefaults(): array
    {
        $defaults = $this->getGuildPoolDefaultData();

        return $this->updateGuildPoolData($defaults);
    }
}
