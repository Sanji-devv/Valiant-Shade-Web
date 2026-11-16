<?php

namespace App\ValiantShade\Services;

use App\Models\Character;
use App\Models\LevelThreshold;
use App\Models\PlayerXp;
use App\Models\Quest;
use App\Models\QuestParticipant;
use App\Models\Rank;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\QuestReportsSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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
     * Initialize the report archive without modifying quest database records.
     */
    public function ensureInitialized(): void
    {
        $path = $this->getStoragePath();
        if (! file_exists($path)) {
            $this->saveReportsData($this->getRawReportsData());
        }
    }

    /**
     * Reset quest reports dataset to the original 32 master reports.
     */
    public function resetToDefaults(): array
    {
        return $this->withArchiveLock(fn () => $this->resetToDefaultsLocked());
    }

    private function resetToDefaultsLocked(): array
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

    public function getAllReportsData(): array
    {
        $this->ensureInitialized();
        $path = $this->getStoragePath();
        $file = fopen($path, 'rb');
        if ($file === false) {
            throw new \RuntimeException('Görev raporları arşivi okunamadı.');
        }
        try {
            if (! flock($file, LOCK_SH)) {
                throw new \RuntimeException('Görev raporları arşivi kilitlenemedi.');
            }
            $json = stream_get_contents($file);
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }

        if ($json === false) {
            throw new \RuntimeException('Görev raporları arşivi okunamadı.');
        }
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! array_is_list($decoded)) {
            throw new \RuntimeException('Görev raporları arşivi geçersiz.');
        }

        return $decoded;
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

    private function withArchiveLock(callable $callback): mixed
    {
        $file = fopen($this->getStoragePath().'.lock', 'c');
        if ($file === false) {
            throw new \RuntimeException('Görev raporları işlem kilidi açılamadı.');
        }
        try {
            if (! flock($file, LOCK_EX)) {
                throw new \RuntimeException('Görev raporları işlem kilidi alınamadı.');
            }
            return $callback();
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    private function transactionWithArchive(callable $callback): mixed
    {
        $path = $this->getStoragePath();
        $original = file_get_contents($path);
        if ($original === false) {
            throw new \RuntimeException('Görev raporları arşivi okunamadı.');
        }

        $writeAttempted = false;
        try {
            return DB::transaction(function () use ($callback, &$writeAttempted) {
                return $callback(function (array $reports) use (&$writeAttempted): void {
                    $writeAttempted = true;
                    $this->saveReportsData($reports);
                });
            });
        } catch (\Throwable $e) {
            if ($writeAttempted && file_put_contents($path, $original, LOCK_EX) === false) {
                throw new \RuntimeException('İşlem başarısız oldu ve rapor arşivi geri yüklenemedi.', 0, $e);
            }
            throw $e;
        }
    }

    public function clearReportsAndPlayerXp(): void
    {
        $this->withArchiveLock(fn () => $this->clearReportsAndPlayerXpLocked());
    }

    private function clearReportsAndPlayerXpLocked(): void
    {
        $this->ensureInitialized();
        $path = $this->getStoragePath();
        $original = file_get_contents($path);
        if ($original === false) {
            throw new \RuntimeException('Görev raporları arşivi okunamadı.');
        }

        $reports = json_decode($original, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($reports) || ! array_is_list($reports)) {
            throw new \RuntimeException('Görev raporları arşivi geçersiz.');
        }

        $writeAttempted = false;
        try {
            DB::transaction(function () use (&$writeAttempted): void {
                DB::table('quest_participants')->delete();
                DB::table('quests')->delete();
                DB::table('xp_ledger')->delete();
                DB::table('player_xps')->update([
                    'level' => 1,
                    'sessions_count' => 0,
                    'rank_xp' => 0,
                    'mission_xp' => 0,
                    'report_xp' => 0,
                    'bailiff_xp' => 0,
                    'extra_xp' => 0,
                    'bonus_xp' => 0,
                    'total_xp' => 0,
                    'inactive_threshold_xp' => null,
                    'updated_at' => now(),
                ]);
                DB::table('characters')->update([
                    'current_level' => 1,
                    'total_xp' => 0,
                    'rank_floor_xp' => 0,
                    'quest_xp' => 0,
                    'report_xp' => 0,
                    'bailiff_xp' => 0,
                    'ex_char_xp' => 0,
                    'wiki_xp' => 0,
                    'updated_at' => now(),
                ]);
                if (Schema::hasTable('character_xp')) {
                    DB::table('character_xp')->update([
                        'current_level' => 1,
                        'total_xp' => 0,
                        'quest_xp' => 0,
                        'report_xp' => 0,
                        'bailiff_xp' => 0,
                        'ex_char_xp' => 0,
                        'wiki_xp' => 0,
                        'updated_at' => now(),
                    ]);
                } else {
                    Log::info('Görev raporları temizlenirken isteğe bağlı character_xp tablosu bulunamadı.');
                }
                DB::table('user_xp')->update([
                    'total_xp' => 0,
                    'ex_char_xp' => 0,
                    'ex_bailiff_xp' => 0,
                    'wiki_xp' => 0,
                    'updated_at' => now(),
                ]);
                DB::table('character_profiles')->update(['level' => 1, 'updated_at' => now()]);

                $writeAttempted = true;
                $this->saveReportsData([]);
            });
        } catch (\Throwable $e) {
            if ($writeAttempted && file_put_contents($path, $original, LOCK_EX) === false) {
                throw new \RuntimeException('İşlem başarısız oldu ve rapor arşivi geri yüklenemedi.', 0, $e);
            }

            throw $e;
        }
    }

    /**
     * Import master-sheet rows into the quest tables and the report archive.
     *
     * @return array{imported: int, skipped: int}
     */
    public function importReports(array $rows): array
    {
        return $this->withArchiveLock(fn () => $this->importReportsLocked($rows));
    }

    private function importReportsLocked(array $rows): array
    {
        if (! Schema::hasTable('quests') || ! Schema::hasTable('quest_participants')) {
            throw new \RuntimeException('Görev raporları tabloları bulunamadı. Önce veritabanı migration işlemlerini çalıştırın.');
        }

        $allReports = $this->getAllReportsData();
        $canonicalKeys = [];
        $archiveGroups = [];
        $nextId = 1;
        foreach ($allReports as $index => $report) {
            $baseKey = $this->reportIdentity((string) ($report['title'] ?? ''), (string) ($report['date'] ?? ''));
            $canonicalKeys[$this->reportPartyIdentity($baseKey, $report['reporter'] ?? '', $report['other_players'] ?? [])][] = $index;
            $archiveGroups[$baseKey][] = $index;
            $nextId = max($nextId, ((int) ($report['id'] ?? 0)) + 1);
        }

        $questGroups = [];
        foreach (Quest::query()->get(['id', 'title', 'quest_date']) as $quest) {
            $questDate = $quest->quest_date instanceof \DateTimeInterface
                ? $quest->quest_date->format('Y-m-d')
                : (string) ($quest->quest_date ?? '');
            $questGroups[$this->reportIdentity($quest->title, $questDate)][] = $quest->id;
        }

        $archiveChanged = false;
        foreach ($archiveGroups as $baseKey => $indices) {
            if (count($indices) === 1 && count($questGroups[$baseKey] ?? []) === 1
                && empty($allReports[$indices[0]]['quest_id'])) {
                $allReports[$indices[0]]['quest_id'] = $questGroups[$baseKey][0];
                $archiveChanged = true;
            }
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
        $seenIncoming = [];
        DB::transaction(function () use (
            $rows,
            &$allReports,
            &$canonicalKeys,
            &$questGroups,
            &$nextId,
            &$imported,
            &$skipped,
            &$seenIncoming,
            &$archiveChanged,
            $characters
        ): void {
            foreach ($rows as $row) {
                if (! is_array($row) || trim((string) ($row['Görev adı'] ?? '')) === '') {
                    $skipped++;

                    continue;
                }

                $title = trim((string) $row['Görev adı']);
                [$displayDate, $databaseDate] = $this->parseImportDate($row['Tarih'] ?? '');
                if ($databaseDate === null) {
                    $skipped++;

                    continue;
                }
                $reporter = trim((string) ($row['Raporlayan'] ?? '')) ?: 'Tolrun';
                $otherPlayers = $this->importParticipantNames($row);
                $baseKey = $this->reportIdentity($title, $databaseDate);
                $key = $this->reportPartyIdentity($baseKey, $reporter, $otherPlayers);
                $occurrence = $seenIncoming[$key] ?? 0;
                $seenIncoming[$key] = $occurrence + 1;
                $existingIndex = $canonicalKeys[$key][$occurrence] ?? null;
                if ($existingIndex !== null && (! empty($allReports[$existingIndex]['quest_id']) || ! empty($questGroups[$baseKey]))) {
                    $skipped++;

                    continue;
                }

                $mqValue = trim((string) ($row['MQ'] ?? ''));
                $isEpic = in_array($mqValue, ['✓✓', '🗸🗸'], true);
                $isMain = $isEpic || in_array(mb_strtolower($mqValue), ['✓', '🗸', '1', 'yes', 'true'], true);
                $mq = $isEpic ? 'epic' : ($isMain ? 'main' : 'none');

                $depoValue = $row['column_F'] ?? null;
                $depoStatus = $depoValue === null
                    ? (float) ($row['Depo'] ?? 0) === 0.0
                    : in_array(mb_strtolower(trim((string) $depoValue)), ['✓', '🗸', '1', 'true', 'yes'], true);
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
                    'id' => $existingIndex !== null ? $allReports[$existingIndex]['id'] : $nextId++,
                    'title' => $title,
                    'date' => $displayDate,
                    'status' => $depoStatus ? 'success' : 'failed',
                    'gm' => trim((string) ($row['GM'] ?? '')) ?: 'Shalvaz',
                    'location' => 'Siwion',
                    'enemies' => 'Canavarlar ve Eşkıyalar',
                    'report_summary' => "{$title} görevi tamamlandı. Toplam {$totalXp} XP dağıtıldı.",
                    'full_report' => "{$title} görevi için kaynak rapor verileri içe aktarıldı.",
                ], $computed);

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

                $questGroups[$baseKey][] = $quest->id;
                if ($existingIndex !== null) {
                    $allReports[$existingIndex]['quest_id'] = $quest->id;
                } else {
                    $report['quest_id'] = $quest->id;
                    $canonicalKeys[$key][] = count($allReports);
                    $allReports[] = $report;
                }
                $archiveChanged = true;
                $imported++;
            }

            if ($archiveChanged) {
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
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $rawDate, $matches)) {
            [, $year, $month, $day] = $matches;
        } elseif (preg_match('/^(\d{1,2})[\/.](\d{1,2})[\/.](\d{4})$/', $rawDate, $matches)) {
            [, $day, $month, $year] = $matches;
        } else {
            return ['', null];
        }

        if (! checkdate((int) $month, (int) $day, (int) $year)) {
            return [$rawDate, null];
        }

        $parsed = Carbon::createFromDate((int) $year, (int) $month, (int) $day);

        return [$parsed->format('d/m/Y'), $parsed->toDateString()];
    }

    private function reportIdentity(string $title, string $date): string
    {
        [, $parsedDate] = $this->parseImportDate($date);
        $date = $parsedDate ?? mb_strtolower(trim($date));

        return mb_strtolower(trim($title)).'|'.$date;
    }

    private function reportPartyIdentity(string $baseKey, string $reporter, mixed $otherPlayers): string
    {
        $players = is_array($otherPlayers) ? $otherPlayers : [];
        $players[] = $reporter;
        $players = array_values(array_unique(array_filter(array_map(
            static fn ($name) => mb_strtolower(trim((string) $name)),
            $players
        ), static fn ($name) => $name !== '' && ! in_array($name, ['-', 'yok'], true))));
        sort($players, SORT_STRING);

        return json_encode([$baseKey, $players], JSON_THROW_ON_ERROR);
    }

    private function validateReportIdentity(array $reports, string $title, string $date, string $reporter, array $otherPlayers, ?int $excludedId = null): array
    {
        [$displayDate, $databaseDate] = $this->parseImportDate($date);
        if ($databaseDate === null) {
            throw ValidationException::withMessages([
                'date' => 'Tarih geçerli bir gün, ay ve yıl içermelidir.',
            ]);
        }

        $key = $this->reportPartyIdentity($this->reportIdentity($title, $databaseDate), $reporter, $otherPlayers);
        foreach ($reports as $report) {
            if ((int) ($report['id'] ?? 0) !== $excludedId
                && $this->reportPartyIdentity(
                    $this->reportIdentity((string) ($report['title'] ?? ''), (string) ($report['date'] ?? '')),
                    $report['reporter'] ?? '',
                    $report['other_players'] ?? []
                ) === $key) {
                throw ValidationException::withMessages([
                    'title' => 'Aynı ad, tarih ve oyuncularla bir görev raporu zaten mevcut.',
                ]);
            }
        }

        return [$displayDate, $databaseDate];
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

        $depoCut = isset($data['depo_cut_override'])
            ? round((float) $data['depo_cut_override'], 2)
            : ($depoStatus ? 0.0 : round($totalXp * 0.20, 2));

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
        if ($netPool < 0) {
            throw ValidationException::withMessages([
                'depo_cut_override' => 'Kesintiler dağıtılabilir toplam XP’yi aşamaz.',
            ]);
        }
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
            'depo_cut_override' => $data['depo_cut_override'] ?? null,
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
    public function createReport(array $data, ?array &$distribution = null): array
    {
        return $this->withArchiveLock(function () use ($data, &$distribution): array {
            return $this->createReportLocked($data, $distribution);
        });
    }

    private function createReportLocked(array $data, ?array &$distribution): array
    {
        if (! Schema::hasTable('quests') || ! Schema::hasTable('quest_participants') ||
            ! Schema::hasTable('quest_report_xp_distributions')) {
            throw new \RuntimeException('Görev ve XP dağıtım tabloları bulunamadı. Veritabanı migration işlemlerini çalıştırın.');
        }

        $all = $this->getAllReportsData();
        $nextId = count($all) > 0 ? max(array_column($all, 'id')) + 1 : 1;

        $data = $this->prepareAutomaticCalculation($data);
        $computed = $this->calculateReportValues($data);

        [$displayDate, $dbQuestDate] = $this->validateReportIdentity(
            $all, $data['title'], $data['date'], $computed['reporter'], $computed['other_players']
        );

        $newReport = array_merge([
            'id' => $nextId,
            'title' => trim($data['title'] ?? 'Yeni Görev Raporu'),
            'date' => $displayDate,
            'status' => $data['status'] ?? 'success',
            'gm' => trim($data['gm'] ?? 'Shalvaz'),
            'location' => trim($data['location'] ?? ''),
            'enemies' => trim($data['enemies'] ?? ''),
            'report_summary' => trim($data['report_summary'] ?? ''),
            'full_report' => trim($data['full_report'] ?? ''),
        ], $computed);

        $this->transactionWithArchive(function (callable $saveReports) use (&$newReport, &$distribution, $data, $dbQuestDate, $all): void {
            if (Schema::hasTable('quests')) {
                $gmChar = Character::where('name', $newReport['gm'])->first();

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
                    'status' => 'APPROVED',
                ]);
                $newReport['quest_id'] = $quest->id;

                DB::table('quest_report_xp_distributions')->insert([
                    'quest_id' => $quest->id,
                    'awards' => '[]',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if (! empty($data['distribute_xp_auto'])) {
                    $distribution = $this->applyReportAwards($quest, $newReport);
                } else {
                    $participants = $this->ensureReportParticipants($quest, $newReport);
                    $this->syncReportParticipants($quest, $newReport, $participants);
                }
            }

            array_unshift($all, $newReport);
            $saveReports($all);
        });

        return $newReport;
    }

    /**
     * Update an existing quest report.
     */
    public function updateReport(int|string $id, array $data): ?array
    {
        return $this->withArchiveLock(fn () => $this->updateReportLocked($id, $data));
    }

    private function updateReportLocked(int|string $id, array $data): ?array
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
        $calculationData = $this->prepareAutomaticCalculation(array_merge($existing, $data));
        $computed = $this->calculateReportValues($calculationData);

        [$displayDate, $dbQuestDate] = $this->validateReportIdentity(
            $all,
            $data['title'] ?? $existing['title'],
            $data['date'] ?? $existing['date'],
            $computed['reporter'],
            $computed['other_players'],
            $id
        );

        $updated = array_merge($existing, [
            'title' => trim($data['title'] ?? $existing['title']),
            'date' => $displayDate,
            'status' => $data['status'] ?? ($existing['status'] ?? 'success'),
            'gm' => trim($data['gm'] ?? ($existing['gm'] ?? 'Shalvaz')),
            'location' => trim(array_key_exists('location', $data) ? ($data['location'] ?? '') : ($existing['location'] ?? '')),
            'enemies' => trim(array_key_exists('enemies', $data) ? ($data['enemies'] ?? '') : ($existing['enemies'] ?? '')),
            'report_summary' => trim(array_key_exists('report_summary', $data) ? ($data['report_summary'] ?? '') : ($existing['report_summary'] ?? '')),
            'full_report' => trim(array_key_exists('full_report', $data) ? ($data['full_report'] ?? '') : ($existing['full_report'] ?? '')),
        ], $computed);

        $qRecord = $this->findReportQuest($existing);
        $xpWarning = null;
        $unlinkedPlayers = [];
        $this->transactionWithArchive(function (callable $saveReports) use ($qRecord, &$updated, &$xpWarning, &$unlinkedPlayers, $dbQuestDate, $foundIndex, $all): void {
            if ($qRecord) {
                $qRecord = Quest::whereKey($qRecord->id)->lockForUpdate()->firstOrFail();
                $qRecord->update([
                    'title' => $updated['title'],
                    'quest_date' => $dbQuestDate,
                    'is_main_quest' => $updated['is_main'],
                    'level' => (int) $updated['level'],
                    'base_xp' => (float) $updated['total_xp'],
                    'depo_fee_xp' => (float) $updated['depo_cut'],
                    'guild_cut_xp' => (float) $updated['guild_cut'],
                    'report_reward_xp' => (float) $updated['rapor_cut'],
                    'bonus_pool_xp' => (float) $updated['extra_reward'],
                    'per_player_xp' => (float) $updated['avg_reward'],
                ]);
                $distribution = DB::table('quest_report_xp_distributions')->where('quest_id', $qRecord->id)->first();
                if ($distribution) {
                    $awards = json_decode($distribution->awards, true, 512, JSON_THROW_ON_ERROR);
                    if ($awards !== []) {
                        $this->reverseReportAwards($awards);
                        $result = $this->applyReportAwards($qRecord, $updated);
                        $unlinkedPlayers = array_column(array_filter(
                            $result['players_awarded'], fn ($award) => $award['character_id'] === null
                        ), 'name');
                    } else {
                        $participants = $this->ensureReportParticipants($qRecord, $updated);
                        $this->syncReportParticipants($qRecord, $updated, $participants);
                    }
                } else {
                    $xpWarning = 'Bu eski raporun XP dağıtımı doğrulanamadığı için karakter XP’leri değiştirilmedi.';
                }
            } else {
                $xpWarning = 'Bu eski raporun XP dağıtımı doğrulanamadığı için karakter XP’leri değiştirilmedi.';
            }

            $all[$foundIndex] = $updated;
            $saveReports($all);
        });

        return array_merge($updated, ['xp_warning' => $xpWarning, 'xp_unlinked' => $unlinkedPlayers]);
    }

    public function getCharacterLevels(): array
    {
        return array_merge(
            PlayerXp::pluck('level', 'character_name')->toArray(),
            Character::pluck('current_level', 'name')->toArray()
        );
    }

    private function prepareAutomaticCalculation(array $data): array
    {
        if (empty($data['auto_party_level'])) {
            return $data;
        }

        $names = array_merge([$data['reporter'] ?? ''], $data['other_players'] ?? []);
        $normalized = array_map(fn ($name) => mb_strtolower(trim((string) $name)), $names);
        if (in_array('', $normalized, true) || count($normalized) !== count(array_unique($normalized))) {
            throw ValidationException::withMessages(['reporter' => 'Kadroda boş veya tekrarlanan karakter adı olamaz.']);
        }

        $levels = [];
        foreach ($this->getCharacterLevels() as $name => $level) {
            $levels[mb_strtolower(trim($name))] = max(1, (int) $level);
        }
        $data['level'] = min(20, (int) round(array_sum(array_map(
            fn ($name) => $levels[$name] ?? 1, $normalized
        )) / count($normalized)));
        $data['auto_calc_xp'] = true;
        unset($data['rapor_cut'], $data['vault_cut']);

        return $data;
    }

    /**
     * Delete a quest report by ID.
     */
    public function deleteReport(int|string $id, ?string &$xpWarning = null): bool
    {
        return $this->withArchiveLock(function () use ($id, &$xpWarning): bool {
            return $this->deleteReportLocked($id, $xpWarning);
        });
    }

    private function deleteReportLocked(int|string $id, ?string &$xpWarning): bool
    {
        $all = $this->getAllReportsData();
        $id = (int) $id;
        $originalCount = count($all);

        $toDelete = collect($all)->firstWhere('id', $id);
        $filtered = array_filter($all, fn ($r) => (int) $r['id'] !== $id);

        if (count($filtered) !== $originalCount) {
            $qRecord = $this->findReportQuest($toDelete);
            $this->transactionWithArchive(function (callable $saveReports) use ($qRecord, $filtered, &$xpWarning): void {
                if ($qRecord) {
                    $qRecord = Quest::whereKey($qRecord->id)->lockForUpdate()->firstOrFail();
                    $distribution = DB::table('quest_report_xp_distributions')->where('quest_id', $qRecord->id)->first();
                    if ($distribution) {
                        $this->reverseReportAwards(json_decode($distribution->awards, true, 512, JSON_THROW_ON_ERROR));
                        DB::table('quest_report_xp_distributions')->where('quest_id', $qRecord->id)->delete();
                    } else {
                        $xpWarning = 'Eski raporun XP dağıtımı doğrulanamadığı için karakter XP’leri değiştirilmedi.';
                    }
                    if (Schema::hasTable('quest_participants')) {
                        QuestParticipant::where('quest_id', $qRecord->id)->delete();
                    }
                    $qRecord->delete();
                } else {
                    $xpWarning = 'Eski raporun XP dağıtımı doğrulanamadığı için karakter XP’leri değiştirilmedi.';
                }
                $saveReports($filtered);
            });

            return true;
        }

        return false;
    }

    private function findReportQuest(array $report): ?Quest
    {
        if (! Schema::hasTable('quests')) {
            return null;
        }
        if (! empty($report['quest_id'])) {
            return Quest::find($report['quest_id']);
        }
        [, $date] = $this->parseImportDate((string) ($report['date'] ?? ''));
        $query = Quest::where('title', $report['title']);
        if ($date !== null) {
            $query->whereDate('quest_date', $date);
        }
        $matches = $query->limit(2)->get();
        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'title' => 'Bu raporun veritabanı kaydı kesin olarak belirlenemedi.',
            ]);
        }

        return $matches->first();
    }

    /**
     * Distribute average XP from a report to Player XP Tracker & Character models.
     */
    public function distributeXpToPlayers(int|string $id): array
    {
        return $this->withArchiveLock(fn () => $this->distributeXpToPlayersLocked($id));
    }

    private function distributeXpToPlayersLocked(int|string $id): array
    {
        $report = $this->getReportById($id);
        if (! $report) {
            throw new \InvalidArgumentException("Görev raporu (#{$id}) bulunamadı.");
        }

        $quest = $this->findReportQuest($report);
        if (! $quest) {
            throw new \InvalidArgumentException('Raporun görev kaydı bulunamadı; XP dağıtımı doğrulanamıyor.');
        }

        return DB::transaction(function () use ($quest, $report): array {
            $quest = Quest::whereKey($quest->id)->lockForUpdate()->firstOrFail();
            $distribution = DB::table('quest_report_xp_distributions')->where('quest_id', $quest->id)->first();
            if (! $distribution) {
                throw new \InvalidArgumentException('Eski raporun XP dağıtım durumu doğrulanamadığı için tekrar dağıtım yapılamaz.');
            }
            if (json_decode($distribution->awards, true, 512, JSON_THROW_ON_ERROR) !== []) {
                throw new \InvalidArgumentException('Bu raporun XP’si zaten dağıtılmış.');
            }

            return $this->applyReportAwards($quest, $report);
        });
    }

    private function ensureReportParticipants(Quest $quest, array $report): array
    {
        $names = array_merge([$report['reporter']], $report['other_players'] ?? []);
        $normalized = array_map(fn ($name) => mb_strtolower(trim((string) $name)), $names);
        if (in_array('', $normalized, true) || count($normalized) !== count(array_unique($normalized))) {
            throw ValidationException::withMessages(['reporter' => 'Kadroda boş veya tekrarlanan karakter adı olamaz.']);
        }
        $charactersByName = Character::get(['id', 'name'])->groupBy(fn ($character) => mb_strtolower(trim($character->name)));
        $playersByName = PlayerXp::get(['id', 'character_name'])->groupBy(fn ($player) => mb_strtolower(trim($player->character_name)));
        $participants = [];

        foreach ($names as $name) {
            $name = trim($name);
            $characters = $charactersByName->get(mb_strtolower($name), collect());
            $players = $playersByName->get(mb_strtolower($name), collect());
            if ($characters->count() > 1 || $players->count() > 1) {
                throw ValidationException::withMessages(['reporter' => "{$name} için birden fazla karakter kaydı bulundu."]);
            }
            $character = $characters->isEmpty() ? null : Character::whereKey($characters->first()->id)->lockForUpdate()->firstOrFail();
            $player = $players->isEmpty() ? null : PlayerXp::whereKey($players->first()->id)->lockForUpdate()->firstOrFail();

            if ($character && $player && $player->user_id && (int) $player->user_id !== (int) $character->user_id) {
                throw ValidationException::withMessages(['reporter' => "{$name} için karakter ve XP kayıtlarının kullanıcıları farklı."]);
            }

            if (! $character) {
                $owner = $player?->user_id ? User::find($player->user_id) : null;
                if ($player?->user_id && ! $owner) {
                    throw ValidationException::withMessages(['reporter' => "{$name} için XP kaydının kullanıcısı bulunamadı."]);
                }
                if (! $owner) {
                    $owner = $this->createUnknownReportOwner($quest);
                }

                $character = new Character([
                    'user_id' => $owner->id,
                    'name' => $player?->character_name ?? $name,
                    'character_type' => 'MAIN',
                    'status' => 'PENDING',
                    'current_level' => $player?->level ?? 1,
                    'total_xp' => $player?->total_xp ?? 0,
                    'rank_floor_xp' => $player?->rank_xp ?? 0,
                    'quest_xp' => $player?->mission_xp ?? 0,
                    'report_xp' => $player?->report_xp ?? 0,
                    'bailiff_xp' => $player?->bailiff_xp ?? 0,
                    'ex_char_xp' => ($player?->extra_xp ?? 0) + ($player?->bonus_xp ?? 0),
                ]);
                $components = (float) $character->rank_floor_xp + (float) $character->quest_xp
                    + (float) $character->report_xp + (float) $character->bailiff_xp
                    + (float) $character->ex_char_xp + (float) $character->wiki_xp;
                if (($components > 0 && abs($components - (float) $character->total_xp) > 0.001)
                    || ($character->total_xp > 0 && LevelThreshold::calculateLevelFromXp($character->total_xp) !== $character->current_level)) {
                    $rank = Character::calculateRankFromLevelAndSessions($character->current_level, $player?->sessions_count ?? 0);
                    $character->rank_id = Rank::where('name', $rank)->value('id');
                    $character->saveQuietly();
                } else {
                    $character->save();
                }
            }

            if (! $player) {
                $player = PlayerXp::create([
                    'user_id' => $character->user_id,
                    'character_name' => $character->name,
                    'player_name' => $character->user?->display_name,
                    'level' => $character->current_level,
                    'sessions_count' => 0,
                    'rank_xp' => (int) round($character->rank_floor_xp ?? 0),
                    'mission_xp' => (int) round($character->quest_xp ?? 0),
                    'report_xp' => (int) round($character->report_xp ?? 0),
                    'bailiff_xp' => (int) round($character->bailiff_xp ?? 0),
                    'extra_xp' => (int) round(($character->ex_char_xp ?? 0) + ($character->wiki_xp ?? 0)),
                    'total_xp' => (int) round($character->total_xp ?? 0),
                ]);
            } elseif (! $player->user_id) {
                $player->user_id = $character->user_id;
                $player->player_name = $player->player_name ?: $character->user?->display_name;
                $player->save();
            }

            $participants[] = ['name' => $name, 'character' => $character, 'player' => $player];
        }

        return $participants;
    }

    private function createUnknownReportOwner(Quest $quest): User
    {
        $date = $quest->quest_date->format('d.m.Y');
        $number = 1;
        do {
            $name = "Unknown {$date} {$number}";
            $username = 'unknown_'.$quest->quest_date->format('Ymd').'_'.$number;
            $email = $username.'@example.invalid';
            $number++;
        } while (PlayerXp::withTrashed()->where('player_name', $name)->exists()
            || User::withTrashed()->where('username', $username)->orWhere('email', $email)
                ->orWhere('name', $name)->orWhere('display_name', $name)->exists());

        return User::create([
            'username' => $username,
            'email' => $email,
            'name' => $name,
            'role' => 'PLAYER',
            'status' => 0,
        ]);
    }

    private function syncReportParticipants(Quest $quest, array $report, array $participants, bool $distributed = false): void
    {
        $quest->participants()->delete();
        foreach ($participants as $index => $participant) {
            QuestParticipant::create([
                'quest_id' => $quest->id,
                'character_id' => $participant['character']->id,
                'earned_quest_xp' => $distributed ? (int) round($report['avg_reward']) : (float) $report['avg_reward'],
                'earned_report_xp' => $index === 0
                    ? ($distributed ? (int) round($report['rapor_cut']) : (float) $report['rapor_cut'])
                    : 0,
                'is_kia' => false,
                'is_mia' => false,
            ]);
        }
        $quest->reporter_character_id = $participants[0]['character']->id;
        $quest->save();
    }

    private function applyReportAwards(Quest $quest, array $report): array
    {
        $questXp = (int) round($report['avg_reward']);
        $reportXp = (int) round($report['rapor_cut'] ?? 0);
        $participants = $this->ensureReportParticipants($quest, $report);
        $awards = [];

        foreach ($participants as $index => $participant) {
            $award = [
                'name' => $participant['name'],
                'player_xp_id' => $participant['player']->id,
                'character_id' => $participant['character']->id,
                'quest_xp' => $questXp,
                'report_xp' => $index === 0 ? $reportXp : 0,
            ];
            $this->adjustAward($award, 1);
            $awards[] = $award;
        }

        $this->syncReportParticipants($quest, $report, $participants, true);
        DB::table('quest_report_xp_distributions')->where('quest_id', $quest->id)->update([
            'awards' => json_encode($awards, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);
        return [
            'report_id' => $report['id'],
            'report_title' => $report['title'],
            'xp_per_player' => $questXp,
            'players_awarded' => $awards,
            'total_awarded_count' => count($awards),
        ];
    }

    private function reverseReportAwards(array $awards): void
    {
        foreach ($awards as $award) {
            $this->adjustAward($award, -1);
        }
    }

    private function adjustAward(array $award, int $direction): void
    {
        $player = PlayerXp::whereKey($award['player_xp_id'])->lockForUpdate()->first();
        $character = $award['character_id'] ? Character::whereKey($award['character_id'])->lockForUpdate()->first() : null;
        if (! $player || ($award['character_id'] && ! $character)) {
            throw ValidationException::withMessages(['reporter' => "{$award['name']} için önceki XP kaydı bulunamadı."]);
        }

        $questXp = $direction * $award['quest_xp'];
        $reportXp = $direction * $award['report_xp'];
        if ($player->mission_xp + $questXp < 0 || $player->report_xp + $reportXp < 0 ||
            $player->total_xp + $questXp + $reportXp < 0 ||
            ($character && ($character->quest_xp + $questXp < 0 || $character->report_xp + $reportXp < 0 ||
                $character->total_xp + $questXp + $reportXp < 0))) {
            throw ValidationException::withMessages(['reporter' => "{$award['name']} için önceki XP güvenle geri alınamıyor."]);
        }

        $player->mission_xp += $questXp;
        $player->report_xp += $reportXp;
        $player->total_xp += $questXp + $reportXp;
        $player->sessions_count = max(0, $player->sessions_count + $direction);
        $player->level = PlayerXp::calculateLevelFromXp($player->total_xp);
        $player->save();

        if ($character) {
            $character->quest_xp += $questXp;
            $character->report_xp += $reportXp;
            $character->total_xp += $questXp + $reportXp;
            $character->current_level = LevelThreshold::calculateLevelFromXp($character->total_xp);
            $character->saveQuietly();
        }

        DB::table('character_profiles')->where('character_name', $character?->name ?? $player->character_name)
            ->update(['level' => $character?->current_level ?? $player->level]);
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
