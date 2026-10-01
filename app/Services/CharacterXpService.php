<?php

namespace App\Services;

use App\Models\Character;
use App\Models\LevelThreshold;

class CharacterXpService
{
    public const CLERK_FLOOR_XP = 3300.00;
    public const UZMAN_FLOOR_XP = 98295.00;

    /**
     * Inactivity Threshold (Sütun N):
     * Karakter durumu 'INACTIVE' veya 'TRAVELER' ise tamamlanan görev sayısına göre taban belirlenir:
     * 0-1: 0, 2-3: 1300, 4-5: 3300, 6-7: 6000, 8-9: 10000, 10+: 15000 XP
     */
    public function calculateInactiveFloor(int $completedQuestsCount): float
    {
        if ($completedQuestsCount <= 1) {
            return 0.00;
        } elseif ($completedQuestsCount <= 3) {
            return 1300.00;
        } elseif ($completedQuestsCount <= 5) {
            return 3300.00;
        } elseif ($completedQuestsCount <= 7) {
            return 6000.00;
        } elseif ($completedQuestsCount <= 9) {
            return 10000.00;
        } else {
            return 15000.00;
        }
    }

    /**
     * Sütun H: Rütbe Tabanı farkı hesabı.
     * Kazanılmış doğrudan XP'ler bu tabandan çıkarılarak aradaki fark kadar rank_floor_xp verilir.
     */
    public function calculateRankFloorXp(float $directEarnedXp, float $targetBaseFloor): float
    {
        $diff = $targetBaseFloor - $directEarnedXp;
        return $diff > 0 ? round($diff, 2) : 0.00;
    }

    /**
     * Sütun D: Toplam XP = H + I + J + K + L + M
     * Total XP = rank_floor_xp + quest_xp + report_xp + bailiff_xp + ex_char_xp + wiki_xp
     */
    public function calculateTotalXp(
        float $rankFloorXp,
        float $questXp,
        float $reportXp,
        float $bailiffXp,
        float $exCharXp,
        float $wikiXp
    ): float {
        return round($rankFloorXp + $questXp + $reportXp + $bailiffXp + $exCharXp + $wikiXp, 2);
    }

    /**
     * Determine dynamic level from Total XP.
     * Margrave (U) rütbesindeki karakterler seviye hesabında baremin +1 üstünde değerlendirilir.
     */
    public function calculateLevel(float $totalXp, ?string $rankName = null): int
    {
        $baseLevel = LevelThreshold::calculateLevelFromXp($totalXp);

        if ($rankName !== null && (
            strcasecmp($rankName, 'Margrave (U)') === 0 ||
            str_contains(strtoupper($rankName), 'MARGRAVE (U)')
        )) {
            return min(20, $baseLevel + 1);
        }

        return $baseLevel;
    }

    /**
     * Synchronize a Character model's XP components, floors, total XP, and current level.
     */
    public function syncCharacter(Character $character): Character
    {
        // 1. Direct earned XP (I + J + K + L + M)
        $questXp = (float) ($character->quest_xp ?? 0.00);
        $reportXp = (float) ($character->report_xp ?? 0.00);
        $bailiffXp = (float) ($character->bailiff_xp ?? 0.00);
        $exCharXp = (float) ($character->ex_char_xp ?? 0.00);
        $wikiXp = (float) ($character->wiki_xp ?? 0.00);

        $directXp = round($questXp + $reportXp + $bailiffXp + $exCharXp + $wikiXp, 2);

        // 2. Determine target base floor
        $rankName = $character->rank?->name ?? '';
        $targetFloor = 0.00;

        if (strcasecmp($character->character_type ?? '', 'CLERK') === 0 || strcasecmp($rankName, 'Clerk') === 0) {
            $targetFloor = self::CLERK_FLOOR_XP;
        } elseif (strcasecmp($rankName, 'Uzman') === 0) {
            $targetFloor = self::UZMAN_FLOOR_XP;
        } elseif ($character->rank && (float) $character->rank->base_floor_xp > 0) {
            $targetFloor = (float) $character->rank->base_floor_xp;
        }

        // 3. Inactive/Traveler check (Sütun N)
        $statusUpper = strtoupper($character->status ?? '');
        $rankUpper = strtoupper($rankName);
        if (in_array($statusUpper, ['INACTIVE', 'TRAVELER']) || in_array($rankUpper, ['INAKTIF', 'INACTIVE', 'GEZGIN', 'TRAVELER'])) {
            $completedQuestsCount = $character->questParticipants()
                ->where('attendance_status', 'COMPLETED')
                ->count();
            if ($completedQuestsCount === 0) {
                $completedQuestsCount = $character->quests()->count();
            }
            $inactiveFloor = $this->calculateInactiveFloor($completedQuestsCount);
            $targetFloor = max($targetFloor, $inactiveFloor);
        }

        // 4. Sütun H: Rank Floor XP
        $rankFloorXp = $this->calculateRankFloorXp($directXp, $targetFloor);
        $character->rank_floor_xp = $rankFloorXp;

        // 5. Sütun D: Total XP
        $totalXp = $this->calculateTotalXp($rankFloorXp, $questXp, $reportXp, $bailiffXp, $exCharXp, $wikiXp);
        $character->total_xp = $totalXp;

        // 6. Level transition with Margrave (U) consideration
        $character->current_level = $this->calculateLevel($totalXp, $rankName);

        $character->save();

        return $character;
    }
}
