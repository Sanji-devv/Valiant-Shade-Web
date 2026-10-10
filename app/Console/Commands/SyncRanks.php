<?php

namespace App\Console\Commands;

use App\Models\Character;
use App\Models\PlayerXp;
use App\Models\Rank;
use Illuminate\Console\Command;

class SyncRanks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranks:sync {--dry-run : Preview changes without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync character and player ranks according to Valiant Shade progression rules.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line("--- SYNCING RANKS ACCORDING TO NEW RULES ---");
        $this->line("Rule 1: Level 1 -> Aday");
        $this->line("Rule 2: Level 2-5 -> Üye");
        $this->line("Rule 3: Level 6-9 -> Kıdemli");
        $this->line("Rule 4: Level 10 (sessions >= 3) -> Uzman, otherwise Kıdemli");
        $this->line("Rule 5: Level 11+ -> Uzman\n");

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn("[DRY RUN MODE: No database changes will be persisted]\n");
        }

        // Cache existing ranks by name
        $ranksByName = Rank::all()->keyBy('name');
        $ranksById = Rank::all()->keyBy('id');

        $specialRankIds = [2, 6, 7, 8]; // Clerk, Margrave, Margrave (U), Temsilci
        $specialRankNames = ['Clerk', 'Inaktif', 'Gezgin', 'Margrave', 'Margrave (U)', 'Temsilci'];

        // 1. Sync Characters
        $characters = Character::whereNotIn('character_type', ['CLERK'])->get();
        $charUpdated = 0;
        $charTotal = $characters->count();

        foreach ($characters as $char) {
            if (in_array((int) $char->rank_id, $specialRankIds, true)) {
                continue;
            }

            $sessionsCount = 0;
            if ($char->exists) {
                $sessionsCount = $char->questParticipants()->count();
            }
            if ($sessionsCount === 0 && !empty($char->name)) {
                $sessionsCount = (int) (PlayerXp::where('character_name', $char->name)->value('sessions_count') ?? 0);
            }

            $expectedRankName = Character::calculateRankFromLevelAndSessions((int) $char->current_level, $sessionsCount);
            $expectedRank = $ranksByName->get($expectedRankName);

            if ($expectedRank && $char->rank_id !== $expectedRank->id) {
                $oldRankName = $ranksById->get($char->rank_id)?->name ?? 'Unknown';
                $this->info("Character [{$char->name}]: {$oldRankName} -> {$expectedRankName} (Lvl: {$char->current_level}, Sessions: {$sessionsCount})");
                $charUpdated++;

                if (!$isDryRun) {
                    $char->rank_id = $expectedRank->id;
                    $char->saveQuietly();
                }
            }
        }

        // 2. Sync PlayerXp records
        $playerXps = PlayerXp::all();
        $playerXpUpdated = 0;
        $playerXpTotal = $playerXps->count();

        foreach ($playerXps as $px) {
            if (in_array($px->rank, $specialRankNames, true)) {
                continue;
            }

            $lvl = $px->level ?: PlayerXp::calculateLevelFromXp((int) $px->total_xp);
            $expectedRankName = PlayerXp::calculateRankFromLevelAndSessions((int) $lvl, (int) $px->sessions_count);

            if ($px->rank !== $expectedRankName) {
                $this->info("PlayerXp [{$px->character_name}]: {$px->rank} -> {$expectedRankName} (Lvl: {$lvl}, Sessions: {$px->sessions_count})");
                $playerXpUpdated++;

                if (!$isDryRun) {
                    $px->rank = $expectedRankName;
                    $px->saveQuietly();
                }
            }
        }

        $this->newLine();
        $this->info("Characters Checked: {$charTotal} | Updated: {$charUpdated}");
        $this->info("Player XP Records Checked: {$playerXpTotal} | Updated: {$playerXpUpdated}");

        // Summary table of current Character distribution
        $charDistribution = Character::join('ranks', 'characters.rank_id', '=', 'ranks.id')
            ->selectRaw('ranks.name as rank_name, count(*) as count')
            ->groupBy('ranks.name')
            ->orderBy('count', 'desc')
            ->get();

        $this->newLine();
        $this->table(
            ['Rank', 'Character Count'],
            $charDistribution->map(fn($row) => [$row->rank_name, $row->count])
        );

        $this->newLine();
        $this->info("Rank synchronization successfully completed!");

        return 0;
    }
}
