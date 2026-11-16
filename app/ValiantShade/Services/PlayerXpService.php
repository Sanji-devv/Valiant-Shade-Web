<?php

namespace App\ValiantShade\Services;

use App\Models\PlayerXp;
use App\Models\GuildSetting;
use App\ValiantShade\Contracts\PlayerXpRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PlayerXpService
{
    public function __construct(
        protected PlayerXpRepositoryInterface $repository
    ) {}

    public function getAdventurers(array $filters = []): Collection
    {
        return $this->repository->all($filters);
    }

    public function getAdventurer(int $id): ?PlayerXp
    {
        return $this->repository->find($id);
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    public function createAdventurer(array $data): PlayerXp
    {
        $data = $this->prepareAdventurerData($data);
        return $this->repository->create($data);
    }

    public function updateAdventurer(int $id, array $data): PlayerXp
    {
        $data = $this->prepareAdventurerData($data);
        return $this->repository->update($id, $data);
    }

    public function deleteAdventurer(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Add Quick XP to an adventurer under a specified category.
     */
    public function addQuickXp(int $id, int $amount, string $category = 'mission', ?string $note = null): PlayerXp
    {
        $adventurer = $this->repository->find($id);
        if (!$adventurer) {
            throw new \InvalidArgumentException("Adventurer with ID {$id} not found.");
        }

        switch ($category) {
            case 'mission':
                $adventurer->mission_xp += $amount;
                $adventurer->sessions_count += 1;
                break;
            case 'report':
                $adventurer->report_xp += $amount;
                break;
            case 'rank':
                $adventurer->rank_xp += $amount;
                break;
            case 'extra':
                $adventurer->extra_xp += $amount;
                break;
            case 'bailiff':
                $adventurer->bailiff_xp += $amount;
                break;
            default:
                $adventurer->mission_xp += $amount;
                break;
        }

        $adventurer->total_xp = $adventurer->calculateComputedTotalXp();
        $adventurer->level = PlayerXp::calculateLevelFromXp($adventurer->total_xp);
        if (!in_array($adventurer->rank, ['Clerk', 'Inaktif', 'Gezgin', 'Margrave', 'Margrave (U)', 'Temsilci'], true)) {
            $adventurer->rank = PlayerXp::calculateRankFromLevelAndSessions($adventurer->level, (int) $adventurer->sessions_count);
        }

        if ($note) {
            $existingNotes = $adventurer->notes ? $adventurer->notes . "\n" : '';
            $adventurer->notes = $existingNotes . "[" . now()->format('Y-m-d H:i') . "] +{$amount} XP ({$category}): {$note}";
        }

        $adventurer->save();
        return $adventurer;
    }

    /**
     * Change status of an adventurer (Active, KIA, MIA, Retired).
     */
    public function updateStatus(int $id, string $status, ?string $reason = null): PlayerXp
    {
        $adventurer = $this->repository->find($id);
        if (!$adventurer) {
            throw new \InvalidArgumentException("Adventurer with ID {$id} not found.");
        }

        $adventurer->status = $status;
        if ($reason !== null) {
            $adventurer->reason = $reason;
        }

        // If reviving back to active
        if ($status === PlayerXp::STATUS_ACTIVE && in_array($adventurer->reason, ['KIA', 'MIA'])) {
            $adventurer->reason = null;
        }

        $adventurer->save();
        return $adventurer;
    }

    /**
     * Batch award XP to multiple adventurers.
     */
    public function batchAddQuickXp(array $ids, int $amount, string $category = 'mission', ?string $note = null): array
    {
        $updated = [];
        foreach ($ids as $id) {
            $updated[] = $this->addQuickXp((int) $id, $amount, $category, $note);
        }
        return $updated;
    }

    /**
     * Bulk update status for multiple adventurers.
     */
    public function bulkUpdateStatus(array $ids, string $status, ?string $reason = null): array
    {
        $updated = [];
        foreach ($ids as $id) {
            $updated[] = $this->updateStatus((int) $id, $status, $reason);
        }
        return $updated;
    }

    /**
     * Compute and normalize adventurer data.
     */
    protected function prepareAdventurerData(array $data): array
    {
        $rankXp = (int) ($data['rank_xp'] ?? 0);
        $missionXp = (int) ($data['mission_xp'] ?? 0);
        $reportXp = (int) ($data['report_xp'] ?? 0);
        $bailiffXp = (int) ($data['bailiff_xp'] ?? 0);
        $extraXp = (int) ($data['extra_xp'] ?? 0);
        $bonusXp = (int) ($data['bonus_xp'] ?? 0);

        $computedXp = $rankXp + $missionXp + $reportXp + $bailiffXp + $extraXp + $bonusXp;

        // If explicit total_xp is provided (e.g. from sheet), honor it; otherwise compute from sub-components
        if (isset($data['total_xp']) && (int) $data['total_xp'] > 0) {
            $totalXp = (int) $data['total_xp'];
        } else {
            $totalXp = $computedXp;
        }

        // Calculate Level strictly based on Pathfinder Fast Progression table
        $level = PlayerXp::calculateLevelFromXp($totalXp);
        $sessionsCount = max(0, (int) ($data['sessions_count'] ?? 1));

        $rank = !empty($data['rank']) ? $data['rank'] : PlayerXp::calculateRankFromLevelAndSessions($level, $sessionsCount);

        return array_merge($data, [
            'rank_xp' => $rankXp,
            'mission_xp' => $missionXp,
            'report_xp' => $reportXp,
            'bailiff_xp' => $bailiffXp,
            'extra_xp' => $extraXp,
            'bonus_xp' => $bonusXp,
            'total_xp' => $totalXp,
            'level' => $level,
            'sessions_count' => $sessionsCount,
            'status' => $data['status'] ?? PlayerXp::STATUS_ACTIVE,
            'rank' => $rank ?? 'Aday',
        ]);
    }

    public function ensureSampleDataSeeded(): void
    {
        if ($this->repository->count() === 0
            && GuildSetting::get('player_xp_tracker_cleared') !== '1'
            && app(QuestReportService::class)->getAllReportsData() !== []) {
            $this->resetToDefaults();
        }
    }

    /**
     * Reset to the exact 64 characters from the Master Sheet dataset.
     */
    public function resetToDefaults(): void
    {
        $this->repository->truncate();

        // 32 Active / Inactive Characters (Left Table)
        $activeAdventurers = [
            ['character_name' => 'Aeglish', 'player_name' => 'Salih ?', 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 266, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 266, 'status' => 'active'],
            ['character_name' => 'Aeris', 'player_name' => 'Gökçen Tuna', 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 213, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 213, 'status' => 'active'],
            ['character_name' => 'Aeswar', 'player_name' => 'Doğukan Özbay', 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 255, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 255, 'status' => 'active'],
            ['character_name' => 'Afatsum', 'player_name' => 'Mustafa Durmaz', 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 213, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 213, 'status' => 'active'],
            ['character_name' => 'Aiden', 'player_name' => null, 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 160, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 160, 'status' => 'active'],
            ['character_name' => 'Aki', 'player_name' => 'Muhammed Akgül', 'rank' => 'Clerk', 'level' => 3, 'sessions_count' => 3, 'rank_xp' => 3300, 'mission_xp' => 711, 'report_xp' => 150, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 4161, 'status' => 'active'],
            ['character_name' => 'Aktar Yağcıoğlu', 'player_name' => 'Ali Berk Ayvaz', 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 255, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 255, 'status' => 'active'],
            ['character_name' => 'Al Di Meola', 'player_name' => 'Berire Güneli', 'rank' => 'Inaktif', 'level' => 2, 'sessions_count' => 2, 'rank_xp' => 640, 'mission_xp' => 510, 'report_xp' => 150, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 1300, 'inactive_threshold_xp' => 1300, 'status' => 'active'],
            ['character_name' => 'Alamir', 'player_name' => 'Cengiz M.M. Öztürk', 'rank' => 'Inaktif', 'level' => 2, 'sessions_count' => 3, 'rank_xp' => 563, 'mission_xp' => 737, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 1300, 'inactive_threshold_xp' => 1300, 'status' => 'active'],
            ['character_name' => 'Alarkyn', 'player_name' => 'Uğurcan Karakaş', 'rank' => 'Inaktif', 'level' => 3, 'sessions_count' => 5, 'rank_xp' => 0, 'mission_xp' => 1231, 'report_xp' => 250, 'bailiff_xp' => 0, 'extra_xp' => 2647, 'total_xp' => 4128, 'inactive_threshold_xp' => 3300, 'status' => 'active'],
            ['character_name' => 'Alberto', 'player_name' => 'Can Tezcan', 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 331, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 331, 'status' => 'active'],
            ['character_name' => 'Aldakoy', 'player_name' => 'Ali Kolaylıoğlu', 'rank' => 'Inaktif', 'level' => 2, 'sessions_count' => 2, 'rank_xp' => 792, 'mission_xp' => 508, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 1300, 'inactive_threshold_xp' => 1300, 'status' => 'active'],
            ['character_name' => 'Aldebaran', 'player_name' => 'Mehmet Helvacı', 'rank' => 'Clerk', 'level' => 3, 'sessions_count' => 3, 'rank_xp' => 3300, 'mission_xp' => 657, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 3957, 'status' => 'active'],
            ['character_name' => 'Aleifkeid', 'player_name' => 'Taylan Özmergen', 'rank' => 'Inaktif', 'level' => 6, 'sessions_count' => 12, 'rank_xp' => 11163, 'mission_xp' => 3657, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 180, 'total_xp' => 15000, 'inactive_threshold_xp' => 15000, 'status' => 'active'],
            ['character_name' => 'Alfred', 'player_name' => null, 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 240, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 240, 'status' => 'active'],
            ['character_name' => 'Alia', 'player_name' => 'Şüheda Ceylan', 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 220, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 220, 'status' => 'active'],
            ['character_name' => 'Alice', 'player_name' => 'Ilgaz Özmen', 'rank' => 'Inaktif', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 121, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 121, 'status' => 'active'],
            ['character_name' => 'Altıgen', 'player_name' => 'Çağlayan Polat', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 284, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 284, 'status' => 'active'],
            ['character_name' => 'Amoril', 'player_name' => 'Burçin Sayım', 'rank' => 'Inaktif', 'level' => 2, 'sessions_count' => 3, 'rank_xp' => 0, 'mission_xp' => 1403, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 1403, 'inactive_threshold_xp' => 1300, 'status' => 'active'],
            ['character_name' => 'Antigonus', 'player_name' => 'Ediz Dikmelik', 'rank' => 'Inaktif', 'level' => 3, 'sessions_count' => 3, 'rank_xp' => 0, 'mission_xp' => 753, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 4894, 'total_xp' => 5646, 'inactive_threshold_xp' => 1300, 'status' => 'active'],
            ['character_name' => 'Antonio', 'player_name' => 'Mertcan Türkarslan', 'rank' => 'Inaktif', 'level' => 3, 'sessions_count' => 4, 'rank_xp' => 2247, 'mission_xp' => 978, 'report_xp' => 75, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 3300, 'inactive_threshold_xp' => 3300, 'status' => 'active'],
            ['character_name' => 'Apha', 'player_name' => 'Yağız Özbah', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 2, 'rank_xp' => 850, 'mission_xp' => 450, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 1300, 'status' => 'active'],
            ['character_name' => 'Archen', 'player_name' => 'Nuri Işık', 'rank' => 'Inaktif', 'level' => 4, 'sessions_count' => 6, 'rank_xp' => 5025, 'mission_xp' => 925, 'report_xp' => 50, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 6000, 'inactive_threshold_xp' => 6000, 'status' => 'active'],
            ['character_name' => 'Arelion', 'player_name' => 'Sebla Demir', 'rank' => 'Inaktif', 'level' => 6, 'sessions_count' => 16, 'rank_xp' => 8588, 'mission_xp' => 5762, 'report_xp' => 650, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 15000, 'inactive_threshold_xp' => 15000, 'status' => 'active'],
            ['character_name' => 'Arelion Vane', 'player_name' => 'İmge Dağlar', 'rank' => 'Uzman', 'level' => 11, 'sessions_count' => 17, 'rank_xp' => 91586, 'mission_xp' => 7790, 'report_xp' => 1750, 'bailiff_xp' => 0, 'extra_xp' => 28015, 'total_xp' => 129141, 'status' => 'active'],
            ['character_name' => 'Argom', 'player_name' => 'Ahmet Kaytarmış', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 255, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 255, 'status' => 'active'],
            ['character_name' => 'Argon Ranfer', 'player_name' => 'Onur Kubilay', 'rank' => 'Kıdemli', 'level' => 7, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 279, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 23103, 'total_xp' => 23382, 'status' => 'active'],
            ['character_name' => 'Argus', 'player_name' => 'Barış Şimşek', 'rank' => 'Gezgin', 'level' => 8, 'sessions_count' => 4, 'rank_xp' => 0, 'mission_xp' => 1958, 'report_xp' => 300, 'bailiff_xp' => 6150, 'extra_xp' => 34389, 'bonus_xp' => 650, 'total_xp' => 43487, 'inactive_threshold_xp' => 3300, 'status' => 'active'],
            ['character_name' => 'Ari (Aday)', 'player_name' => 'İdil Yaycıoğlu', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 213, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 213, 'status' => 'active'],
            ['character_name' => 'Ari (Inaktif)', 'player_name' => 'Deniz Semiz', 'rank' => 'Inaktif', 'level' => 6, 'sessions_count' => 12, 'rank_xp' => 3998, 'mission_xp' => 4832, 'report_xp' => 1550, 'bailiff_xp' => 0, 'extra_xp' => 4620, 'total_xp' => 15000, 'inactive_threshold_xp' => 15000, 'status' => 'active'],
            ['character_name' => 'Arin', 'player_name' => 'Barış Bingöl', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 293, 'report_xp' => 225, 'bailiff_xp' => 0, 'extra_xp' => 848, 'total_xp' => 1366, 'status' => 'active'],
            ['character_name' => 'Arkhan', 'player_name' => 'Baran Sözmen', 'rank' => 'Clerk', 'level' => 3, 'sessions_count' => 1, 'rank_xp' => 3300, 'mission_xp' => 177, 'report_xp' => 0, 'bailiff_xp' => 0, 'extra_xp' => 0, 'total_xp' => 3477, 'status' => 'active'],
        ];

        // 32 Fallen / Missing Characters (Right Table - Hall of Heroes)
        $fallenAdventurers = [
            ['character_name' => '3. Balkabağı', 'player_name' => 'Gür Gün Gürsel', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 2, 'rank_xp' => 0, 'mission_xp' => 520, 'report_xp' => 0, 'total_xp' => 260, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Abraham', 'player_name' => 'Mert Doyurgan', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 15, 'rank_xp' => 0, 'mission_xp' => 6087, 'report_xp' => 300, 'total_xp' => 3194, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Ace', 'player_name' => 'Yavuz Baran Yaman', 'rank' => 'Üye', 'level' => 3, 'sessions_count' => 9, 'rank_xp' => 0, 'mission_xp' => 3681, 'report_xp' => 0, 'total_xp' => 3313, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Achelie', 'player_name' => 'Tuğçe Köseoğlu', 'rank' => 'Üye', 'level' => 3, 'sessions_count' => 9, 'rank_xp' => 0, 'mission_xp' => 3513, 'report_xp' => 1050, 'total_xp' => 4106, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Adonis', 'player_name' => 'Mert Doyurgan', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 354, 'report_xp' => 0, 'total_xp' => 177, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Adran', 'player_name' => 'Mert Çelik', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 8, 'rank_xp' => 0, 'mission_xp' => 3403, 'report_xp' => 75, 'total_xp' => 3130, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Aeglor', 'player_name' => 'Göker Erol', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 2, 'rank_xp' => 0, 'mission_xp' => 1094, 'report_xp' => 625, 'total_xp' => 1547, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Aelinor', 'player_name' => 'Dilan Kırarı', 'rank' => 'Üye', 'level' => 3, 'sessions_count' => 28, 'rank_xp' => 0, 'mission_xp' => 7302, 'report_xp' => 250, 'total_xp' => 3776, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Agah', 'player_name' => 'Ozan Akca', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 18, 'rank_xp' => 0, 'mission_xp' => 5266, 'report_xp' => 0, 'total_xp' => 2633, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Agnian', 'player_name' => 'Dincer Dinçsoy', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 5, 'rank_xp' => 0, 'mission_xp' => 1042, 'report_xp' => 0, 'total_xp' => 937, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Ajay', 'player_name' => 'Göker Erol', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 2, 'rank_xp' => 0, 'mission_xp' => 788, 'report_xp' => 0, 'total_xp' => 394, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Akael', 'player_name' => 'Furkan Ünal', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 2, 'rank_xp' => 0, 'mission_xp' => 815, 'report_xp' => 200, 'total_xp' => 914, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Aldric', 'player_name' => 'Başar Erdem', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 19, 'rank_xp' => 0, 'mission_xp' => 5351, 'report_xp' => 825, 'total_xp' => 3088, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Aldur', 'player_name' => 'Hakan Körpe', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 4, 'rank_xp' => 0, 'mission_xp' => 1125, 'report_xp' => 0, 'total_xp' => 563, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Alek', 'player_name' => 'Mehmet Ali Bahit', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 6, 'rank_xp' => 0, 'mission_xp' => 2525, 'report_xp' => 0, 'total_xp' => 2272, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Aleron', 'player_name' => 'Hicran Ata', 'rank' => 'Üye', 'level' => 2, 'sessions_count' => 9, 'rank_xp' => 0, 'mission_xp' => 2560, 'report_xp' => 300, 'total_xp' => 1430, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Alex', 'player_name' => 'Onur Kubilay', 'rank' => 'Uzman', 'level' => 5, 'sessions_count' => 34, 'rank_xp' => 0, 'mission_xp' => 12759, 'report_xp' => 2050, 'total_xp' => 13328, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Allerion', 'player_name' => 'Ozan İduğ', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 797, 'report_xp' => 0, 'total_xp' => 398, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Almor', 'player_name' => 'Adnan Özacar', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 2, 'rank_xp' => 0, 'mission_xp' => 342, 'report_xp' => 0, 'total_xp' => 308, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Altu', 'player_name' => 'Erdem Uğur Şahin', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 3, 'rank_xp' => 0, 'mission_xp' => 696, 'report_xp' => 0, 'total_xp' => 348, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Amon', 'player_name' => 'Bartu Bilekli', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 2, 'rank_xp' => 0, 'mission_xp' => 613, 'report_xp' => 0, 'total_xp' => 552, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Anduin', 'player_name' => 'Onur Kubilay', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 3, 'rank_xp' => 0, 'mission_xp' => 1045, 'report_xp' => 275, 'total_xp' => 660, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Anton', 'player_name' => 'Furkan Ünal', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 163, 'report_xp' => 0, 'total_xp' => 81, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Antonia', 'player_name' => 'Semih Uğurlubaş', 'rank' => 'Üye', 'level' => 3, 'sessions_count' => 32, 'rank_xp' => 0, 'mission_xp' => 11005, 'report_xp' => 325, 'total_xp' => 5665, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Antonio Merciless', 'player_name' => 'Mertcan Türkarslan', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 2, 'rank_xp' => 0, 'mission_xp' => 278, 'report_xp' => 0, 'total_xp' => 250, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Archibald', 'player_name' => 'Bartu Bilekli', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 2, 'rank_xp' => 0, 'mission_xp' => 553, 'report_xp' => 0, 'total_xp' => 497, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Ardhart', 'player_name' => 'Volkan Öztaşçı', 'rank' => 'Uzman', 'level' => 5, 'sessions_count' => 47, 'rank_xp' => 0, 'mission_xp' => 22474, 'report_xp' => 1175, 'total_xp' => 11799, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Ardmel', 'player_name' => 'Erdem Uğur Şahin', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 3, 'rank_xp' => 0, 'mission_xp' => 786, 'report_xp' => 0, 'total_xp' => 393, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Arganar', 'player_name' => 'Furkan Ünal', 'rank' => 'Uzman', 'level' => 5, 'sessions_count' => 33, 'rank_xp' => 0, 'mission_xp' => 14283, 'report_xp' => 1900, 'total_xp' => 14565, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Argyle', 'player_name' => 'Semih Uğurlubaş', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 1, 'rank_xp' => 0, 'mission_xp' => 270, 'report_xp' => 0, 'total_xp' => 135, 'status' => 'kia', 'reason' => 'KIA'],
            ['character_name' => 'Arianni', 'player_name' => 'Serhat Eldem', 'rank' => 'Kıdemli', 'level' => 6, 'sessions_count' => 49, 'rank_xp' => 0, 'mission_xp' => 20609, 'report_xp' => 1200, 'total_xp' => 19628, 'status' => 'mia', 'reason' => 'MIA'],
            ['character_name' => 'Ariel', 'player_name' => 'Nazlıcan Kat', 'rank' => 'Aday', 'level' => 1, 'sessions_count' => 3, 'rank_xp' => 0, 'mission_xp' => 749, 'report_xp' => 0, 'total_xp' => 449, 'status' => 'kia', 'reason' => 'KIA'],
        ];

        foreach (array_merge($activeAdventurers, $fallenAdventurers) as $data) {
            $this->createAdventurer($data);
        }
    }

    /**
     * Generate CSV content for adventurers.
     */
    public function exportCsv(?string $status = null): string
    {
        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }

        $items = $this->repository->all($filters);

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'ID', 'Character Name', 'Player Name', 'Status', 'Rank',
            'Level', 'Sessions', 'Rank XP', 'Mission XP', 'Report XP',
            'Bailiff XP', 'Extra XP', 'Bonus XP', 'Total XP', 'Reason'
        ]);

        foreach ($items as $item) {
            fputcsv($output, [
                $item->id,
                $item->character_name,
                $item->player_name ?? '',
                $item->status,
                $item->rank,
                $item->level,
                $item->sessions_count,
                $item->rank_xp,
                $item->mission_xp,
                $item->report_xp,
                $item->bailiff_xp,
                $item->extra_xp,
                $item->bonus_xp,
                $item->total_xp,
                $item->reason ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }

    public function resetToExcelDefaults(): void
    {
        $this->resetToDefaults();
    }
}
