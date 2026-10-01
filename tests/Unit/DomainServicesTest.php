<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\CharacterGraveyard;
use App\Models\Feat;
use App\Models\FinancialLedger;
use App\Models\GuildVault;
use App\Models\Quest;
use App\Models\QuestParticipant;
use App\Models\Rank;
use App\Models\User;
use App\Services\CharacterXpService;
use App\Services\GraveyardService;
use App\Services\GuildAccountingService;
use App\Services\QuestCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainServicesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Requirement 4.1:
     * 10 kişilik bir partide 3000 Base XP, %5 Lonca, %10 Rapor ve 1000 Ek XP ile
     * kişi başı 355.00 XP çıktığını doğrula.
     */
    public function test_quest_calculation_service_10_players_3000_base_xp(): void
    {
        $service = new QuestCalculationService();

        $baseXp = 3000.00;
        $bonusXp = 1000.00;
        $participantCount = 10;

        // 1. Service pool calculations
        $pool = $service->calculatePool($baseXp, $bonusXp, false);

        // Lonca Kesintisi (%5) = 3000 * 0.05 = 150.00
        $this->assertEquals(150.00, $pool['guild_cut_xp']);

        // Rapor Ödülü (%10) = 3000 * 0.10 = 300.00
        $this->assertEquals(300.00, $pool['report_reward_xp']);

        // Depo Kesintisi = 0.00 (başarılı)
        $this->assertEquals(0.00, $pool['depo_fee_xp']);

        // Net Havuz = 3000 - 150 - 300 + 1000 = 3550.00
        $this->assertEquals(3550.00, $pool['net_pool_xp']);

        // Kişi Başı Ortalama XP = 3550.00 / 10 = 355.00 XP
        $perPlayerXp = $service->calculatePerPlayerXp($pool['net_pool_xp'], $participantCount);
        $this->assertEquals(355.00, $perPlayerXp);

        // 2. Integration with Quest model and distribution
        $user = User::factory()->create();
        $gmChar = Character::create(['user_id' => $user->id, 'name' => 'GM Char', 'status' => 'ACTIVE']);
        $reporterChar = Character::create(['user_id' => $user->id, 'name' => 'Reporter Char', 'status' => 'ACTIVE']);

        $quest = Quest::create([
            'title' => 'The Great Expedition',
            'base_xp' => $baseXp,
            'bonus_pool_xp' => $bonusXp,
            'gm_character_id' => $gmChar->id,
            'reporter_character_id' => $reporterChar->id,
            'board_status' => 'IN_PROGRESS',
        ]);

        // Add 10 participants (including reporter)
        $participants = [];
        $participants[] = QuestParticipant::create([
            'quest_id' => $quest->id,
            'character_id' => $reporterChar->id,
            'attendance_status' => QuestParticipant::STATUS_ACCEPTED,
        ]);

        for ($i = 2; $i <= 10; $i++) {
            $char = Character::create(['user_id' => $user->id, 'name' => "Member {$i}", 'status' => 'ACTIVE']);
            $participants[] = QuestParticipant::create([
                'quest_id' => $quest->id,
                'character_id' => $char->id,
                'attendance_status' => QuestParticipant::STATUS_ACCEPTED,
            ]);
        }

        $result = $service->distributeRewards($quest);

        $this->assertEquals(355.00, $result['per_player_xp']);
        $quest->refresh();
        $this->assertEquals(355.00, $quest->per_player_xp);
        $this->assertEquals(3550.00, $quest->net_pool_xp);

        // Reporter should get per_player (355.00) + report_reward (300.00) = 655.00 XP
        $reporterParticipant = $quest->participants()->where('character_id', $reporterChar->id)->first();
        $this->assertEquals(355.00, $reporterParticipant->earned_quest_xp);
        $this->assertEquals(300.00, $reporterParticipant->earned_report_xp);

        // Other participants should get exactly 355.00 XP
        $otherParticipant = $quest->participants()->where('character_id', '!=', $reporterChar->id)->first();
        $this->assertEquals(355.00, $otherParticipant->earned_quest_xp);
        $this->assertEquals(0.00, $otherParticipant->earned_report_xp);
    }

    /**
     * Requirement 4.2:
     * KIA durumunda %50, MIA durumunda %90, Bailiff için %50 miras kaldığını test et.
     * Clerk karakterlerin miras alamadığını (0 XP) doğrula.
     */
    public function test_graveyard_inheritance_kia_mia_and_bailiff_multipliers(): void
    {
        $service = new GraveyardService();

        $questXp = 1000.00;
        $reportXp = 200.00;
        $wikiXp = 100.00;
        $exCharXp = 500.00;
        $bailiffXp = 400.00;

        // Düzenli Havuz = 1000 + 200 + 100 + 500 = 1800.00
        $regularPool = 1800.00;

        // --- KIA Senaryosu (%50 regular, %50 bailiff) ---
        $kiaResult = $service->calculateTransferredXp('KIA', $questXp, $reportXp, $wikiXp, $exCharXp, $bailiffXp);
        $this->assertEquals(0.50, $kiaResult['rate']);
        $this->assertEquals(0.50, $kiaResult['bailiff_rate']);
        $this->assertEquals($regularPool, $kiaResult['regular_pool']);
        // (1800 * 0.50) + (400 * 0.50) = 900 + 200 = 1100.00
        $this->assertEquals(1100.00, $kiaResult['transferred_xp']);

        // --- MIA Senaryosu (%90 regular, %50 bailiff) ---
        $miaResult = $service->calculateTransferredXp('MIA', $questXp, $reportXp, $wikiXp, $exCharXp, $bailiffXp);
        $this->assertEquals(0.90, $miaResult['rate']);
        $this->assertEquals(0.50, $miaResult['bailiff_rate']);
        // (1800 * 0.90) + (400 * 0.50) = 1620 + 200 = 1820.00
        $this->assertEquals(1820.00, $miaResult['transferred_xp']);

        // --- Database Snapshot & Clerk/Main Miras Enjeksiyonu ---
        $user = User::factory()->create();
        $deadChar = Character::create([
            'user_id' => $user->id,
            'name' => 'Fallen Hero',
            'status' => 'ACTIVE',
            'quest_xp' => $questXp,
            'report_xp' => $reportXp,
            'wiki_xp' => $wikiXp,
            'ex_char_xp' => $exCharXp,
            'bailiff_xp' => $bailiffXp,
        ]);

        $graveyardRecord = $service->recordDeathSnapshot($deadChar, 'KIA');
        $this->assertEquals('KIA', $deadChar->fresh()->status);
        $this->assertEquals(1100.00, $graveyardRecord->transferred_xp);

        // MAIN Karakter miras alabilmeli
        $mainChar = Character::create([
            'user_id' => $user->id,
            'name' => 'Next Main Hero',
            'character_type' => 'MAIN',
            'status' => 'ACTIVE',
        ]);
        $service->injectInheritance($mainChar);
        $this->assertEquals(1100.00, $mainChar->fresh()->ex_char_xp);

        // CLERK Karakter miras alamaz (ex_char_xp = 0)
        $clerkChar = Character::create([
            'user_id' => $user->id,
            'name' => 'Guild Clerk Officer',
            'character_type' => 'CLERK',
            'status' => 'ACTIVE',
        ]);
        $service->injectInheritance($clerkChar);
        $this->assertEquals(0.00, $clerkChar->fresh()->ex_char_xp);
    }

    /**
     * Requirement 4.3:
     * Lonca kasası harcamasında `character_id = null` iken transaction'ın başarıyla çalıştığını teyit et.
     */
    public function test_guild_vault_expense_with_null_character_id(): void
    {
        $service = new GuildAccountingService();

        // 1. Create Guild Vault with initial balance 5000 SP
        $vault = GuildVault::create([
            'name' => 'Valiant Main Vault',
            'balance_sp' => 5000.00,
        ]);

        // 2. Perform corporate guild expense transaction (character_id is null)
        $expenseAmount = 1250.00;
        $ledgerEntry = $service->recordGuildExpense(
            $vault->id,
            $expenseAmount,
            'Guild Hall fortification repairs'
        );

        // Verify transaction result
        $this->assertNotNull($ledgerEntry->id);
        $this->assertNull($ledgerEntry->character_id);
        $this->assertEquals($vault->id, $ledgerEntry->guild_vault_id);
        $this->assertEquals(FinancialLedger::ENTITY_GUILD_RESERVE, $ledgerEntry->entity_type);
        $this->assertEquals(-1250.00, $ledgerEntry->amount_sp);
        $this->assertEquals(3750.00, $ledgerEntry->balance_after_sp);
        $this->assertEquals('WITHDRAWAL', $ledgerEntry->transaction_type);

        // Verify Vault balance updated
        $vault->refresh();
        $this->assertEquals(3750.00, (float) $vault->balance_sp);

        // 3. Verify Scopes
        $user = User::factory()->create();
        $char = Character::create(['user_id' => $user->id, 'name' => 'Test Character', 'allowance_sp' => 100.00]);
        $service->recordCharacterShare($char->id, 50.00, 'Mission reward');

        $guildEntries = FinancialLedger::forGuild()->get();
        $this->assertTrue($guildEntries->contains('id', $ledgerEntry->id));

        $charEntries = FinancialLedger::forCharacter($char->id)->get();
        $this->assertFalse($charEntries->contains('id', $ledgerEntry->id));
    }

    /**
     * Extra Verification: Character XP calculations, dynamic levels, and Margrave (U).
     */
    public function test_character_xp_service_floors_and_margrave_u(): void
    {
        $xpService = new CharacterXpService();

        // Inactive floor thresholds (Sütun N)
        $this->assertEquals(0.00, $xpService->calculateInactiveFloor(0));
        $this->assertEquals(0.00, $xpService->calculateInactiveFloor(1));
        $this->assertEquals(1300.00, $xpService->calculateInactiveFloor(2));
        $this->assertEquals(1300.00, $xpService->calculateInactiveFloor(3));
        $this->assertEquals(3300.00, $xpService->calculateInactiveFloor(4));
        $this->assertEquals(3300.00, $xpService->calculateInactiveFloor(5));
        $this->assertEquals(6000.00, $xpService->calculateInactiveFloor(6));
        $this->assertEquals(6000.00, $xpService->calculateInactiveFloor(7));
        $this->assertEquals(10000.00, $xpService->calculateInactiveFloor(8));
        $this->assertEquals(10000.00, $xpService->calculateInactiveFloor(9));
        $this->assertEquals(15000.00, $xpService->calculateInactiveFloor(10));
        $this->assertEquals(15000.00, $xpService->calculateInactiveFloor(15));

        // Clerk fixed floor (3300)
        $this->assertEquals(3300.00, CharacterXpService::CLERK_FLOOR_XP);
        // Uzman fixed floor (98295)
        $this->assertEquals(98295.00, CharacterXpService::UZMAN_FLOOR_XP);

        // Margrave (U) gets +1 level
        // e.g. at 0 XP, normal level is 1; Margrave (U) becomes 2
        $normalLevel = $xpService->calculateLevel(0, 'Aday');
        $this->assertEquals(1, $normalLevel);

        $margraveLevel = $xpService->calculateLevel(0, 'Margrave (U)');
        $this->assertEquals(2, $margraveLevel);
    }

    /**
     * Extra Verification: Quest scopes and relations.
     */
    public function test_quest_scopes_and_character_relations(): void
    {
        $user = User::factory()->create();
        $char = Character::create(['user_id' => $user->id, 'name' => 'Recruit', 'status' => 'ACTIVE']);

        $recruitingQuest = Quest::create([
            'title' => 'Recruiting Quest',
            'board_status' => 'RECRUITING',
            'min_level' => 1,
            'max_level' => 5,
            'max_party_size' => 4,
        ]);

        $approvedQuest = Quest::create([
            'title' => 'Approved Quest',
            'board_status' => 'APPROVED',
            'min_level' => 1,
            'max_level' => 10,
        ]);

        $this->assertTrue(Quest::recruiting()->get()->contains('id', $recruitingQuest->id));
        $this->assertFalse(Quest::recruiting()->get()->contains('id', $approvedQuest->id));

        $this->assertTrue(Quest::approved()->get()->contains('id', $approvedQuest->id));
        $this->assertFalse(Quest::approved()->get()->contains('id', $recruitingQuest->id));

        // Character activeQuests relation
        QuestParticipant::create([
            'quest_id' => $recruitingQuest->id,
            'character_id' => $char->id,
            'attendance_status' => QuestParticipant::STATUS_APPLIED,
        ]);

        $this->assertEquals(1, $char->activeQuests()->count());
        $this->assertEquals($recruitingQuest->id, $char->activeQuests()->first()->id);
    }
}
