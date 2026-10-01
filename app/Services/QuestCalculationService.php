<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\QuestParticipant;

class QuestCalculationService
{
    public const GUILD_CUT_RATE = 0.05;
    public const DEPO_FEE_RATE = 0.20;
    public const REPORT_REWARD_RATE = 0.10;

    /**
     * Calculate Base XP from quest level:
     * Base XP = (Level * 250) + 500
     */
    public function calculateBaseXp(int $level): float
    {
        return round(($level * 250) + 500, 2);
    }

    /**
     * Calculate pool breakdown based on base XP, bonus XP, and depo failure status.
     *
     * Net Havuz = Base XP - Lonca Kesintisi (5%) - Depo Kesintisi (20% if fail) - Rapor Ödülü (10%) + Bonus XP
     *
     * @return array{
     *     base_xp: float,
     *     guild_cut_xp: float,
     *     depo_fee_xp: float,
     *     report_reward_xp: float,
     *     bonus_pool_xp: float,
     *     net_pool_xp: float
     * }
     */
    public function calculatePool(
        float $baseXp,
        float $bonusXp = 0.0,
        bool $depoFailed = false,
        ?float $customGuildCut = null,
        ?float $customReportReward = null
    ): array {
        $guildCut = $customGuildCut !== null
            ? round($customGuildCut, 2)
            : round($baseXp * self::GUILD_CUT_RATE, 2);

        $depoFee = $depoFailed
            ? round($baseXp * self::DEPO_FEE_RATE, 2)
            : 0.00;

        $reportReward = $customReportReward !== null
            ? round($customReportReward, 2)
            : round($baseXp * self::REPORT_REWARD_RATE, 2);

        $netPool = round($baseXp - $guildCut - $depoFee - $reportReward + $bonusXp, 2);

        return [
            'base_xp' => round($baseXp, 2),
            'guild_cut_xp' => $guildCut,
            'depo_fee_xp' => $depoFee,
            'report_reward_xp' => $reportReward,
            'bonus_pool_xp' => round($bonusXp, 2),
            'net_pool_xp' => max(0.0, $netPool),
        ];
    }

    /**
     * Calculate Per Player Average XP:
     * Ortalama XP = Net Havuz / Toplam Katılımcı Sayısı
     */
    public function calculatePerPlayerXp(float $netPool, int $participantCount): float
    {
        if ($participantCount <= 0) {
            return 0.00;
        }

        return round($netPool / $participantCount, 2);
    }

    /**
     * Calculate and distribute rewards for a Quest and its participants.
     * Normal participant receives Ortalama XP.
     * Report author receives Ortalama XP + Rapor Ödülü.
     */
    public function distributeRewards(Quest $quest, bool $depoFailed = false): array
    {
        $baseXp = (float) ($quest->base_xp > 0 ? $quest->base_xp : $this->calculateBaseXp($quest->level ?? 1));
        $bonusXp = (float) ($quest->bonus_pool_xp ?? 0.0);

        $pool = $this->calculatePool($baseXp, $bonusXp, $depoFailed);

        $participants = $quest->participants()->get();
        $participantCount = $participants->count();
        $perPlayerXp = $this->calculatePerPlayerXp($pool['net_pool_xp'], $participantCount);

        // Update Quest model
        $quest->update([
            'base_xp' => $pool['base_xp'],
            'guild_cut_xp' => $pool['guild_cut_xp'],
            'depo_fee_xp' => $pool['depo_fee_xp'],
            'report_reward_xp' => $pool['report_reward_xp'],
            'bonus_pool_xp' => $pool['bonus_pool_xp'],
            'net_pool_xp' => $pool['net_pool_xp'],
            'per_player_xp' => $perPlayerXp,
        ]);

        $distributed = [];

        foreach ($participants as $participant) {
            $isReporter = ($participant->character_id !== null && $participant->character_id === $quest->reporter_character_id);
            $earnedReportXp = $isReporter ? $pool['report_reward_xp'] : 0.00;

            $participant->update([
                'earned_quest_xp' => $perPlayerXp,
                'earned_report_xp' => $earnedReportXp,
                'attendance_status' => QuestParticipant::STATUS_COMPLETED,
            ]);

            if ($participant->character) {
                $char = $participant->character;
                $char->quest_xp = (float) $char->questParticipants()->sum('earned_quest_xp');
                $char->report_xp = (float) $char->questParticipants()->sum('earned_report_xp');
                $char->recalculateTotalXp();
            }

            $distributed[] = [
                'character_id' => $participant->character_id,
                'is_reporter' => $isReporter,
                'quest_xp' => $perPlayerXp,
                'report_xp' => $earnedReportXp,
                'total_earned' => round($perPlayerXp + $earnedReportXp, 2),
            ];
        }

        return [
            'quest_id' => $quest->id,
            'pool' => $pool,
            'per_player_xp' => $perPlayerXp,
            'distributions' => $distributed,
        ];
    }
}
