<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterGraveyard;
use Illuminate\Support\Facades\DB;

class GraveyardService
{
    public const KIA_RATE = 0.50;
    public const MIA_RATE = 0.90;
    public const BAILIFF_RATE = 0.50;

    /**
     * Calculate transferred inheritance XP:
     * Düzenli Havuz = quest_xp + report_xp + wiki_xp + ex_char_xp
     * Katsayı: KIA için 0.50, MIA için 0.90
     * Bailiff Katsayısı: Her zaman 0.50
     * Transferred XP = (Düzenli Havuz * Katsayı) + (bailiff_xp * 0.50)
     */
    public function calculateTransferredXp(
        string $cause,
        float $questXp,
        float $reportXp,
        float $wikiXp,
        float $exCharXp,
        float $bailiffXp
    ): array {
        $causeUpper = strtoupper($cause);
        $rate = ($causeUpper === 'MIA') ? self::MIA_RATE : self::KIA_RATE;

        $regularPool = round($questXp + $reportXp + $wikiXp + $exCharXp, 2);
        $regularTransferred = round($regularPool * $rate, 2);
        $bailiffTransferred = round($bailiffXp * self::BAILIFF_RATE, 2);
        $transferredXp = round($regularTransferred + $bailiffTransferred, 2);

        return [
            'cause' => $causeUpper,
            'regular_pool' => $regularPool,
            'rate' => $rate,
            'regular_transferred' => $regularTransferred,
            'bailiff_xp' => round($bailiffXp, 2),
            'bailiff_rate' => self::BAILIFF_RATE,
            'bailiff_transferred' => $bailiffTransferred,
            'transferred_xp' => $transferredXp,
        ];
    }

    /**
     * Record character death snapshot into character_graveyard table.
     */
    public function recordDeathSnapshot(
        Character $character,
        string $cause,
        ?int $deathQuestId = null
    ): CharacterGraveyard {
        return DB::transaction(function () use ($character, $cause, $deathQuestId) {
            $causeUpper = in_array(strtoupper($cause), ['KIA', 'MIA']) ? strtoupper($cause) : 'KIA';

            $calculation = $this->calculateTransferredXp(
                $causeUpper,
                (float) ($character->quest_xp ?? 0.0),
                (float) ($character->report_xp ?? 0.0),
                (float) ($character->wiki_xp ?? 0.0),
                (float) ($character->ex_char_xp ?? 0.0),
                (float) ($character->bailiff_xp ?? 0.0)
            );

            // Update character status
            $character->status = $causeUpper;
            $character->save();

            // Create graveyard record
            return CharacterGraveyard::create([
                'user_id' => $character->user_id,
                'character_id' => $character->id,
                'cause' => $causeUpper,
                'quest_count_at_death' => $character->quests()->count(),
                'quest_xp_at_death' => (float) ($character->quest_xp ?? 0.0),
                'report_xp_at_death' => (float) ($character->report_xp ?? 0.0),
                'transferred_xp' => $calculation['transferred_xp'],
                'death_quest_id' => $deathQuestId,
                'recorded_at' => now(),
            ]);
        });
    }

    /**
     * Calculate available unspent or total inheritance XP from graveyard records for a user.
     * CLERK characters cannot inherit (ex_char_xp = 0).
     */
    public function getInheritableXpForUser(int $userId, string $characterType = 'MAIN'): float
    {
        if (strcasecmp($characterType, 'CLERK') === 0) {
            return 0.00;
        }

        return (float) CharacterGraveyard::where('user_id', $userId)->sum('transferred_xp');
    }

    /**
     * Inject transferred inheritance XP to a newly created character.
     * Yeni açılan MAIN karakterin ex_char_xp sütununa oyuncunun mezarlıktaki toplam devir bakiyesi enjekte edilir.
     * CLERK karakterler miras alamaz (ex_char_xp = 0).
     */
    public function injectInheritance(Character $newCharacter): Character
    {
        $charType = strtoupper($newCharacter->character_type ?? 'MAIN');

        if ($charType === 'CLERK') {
            $newCharacter->ex_char_xp = 0.00;
        } else {
            $newCharacter->ex_char_xp = $this->getInheritableXpForUser((int) $newCharacter->user_id, $charType);
        }

        $newCharacter->recalculateTotalXp();
        $newCharacter->save();

        return $newCharacter;
    }
}
