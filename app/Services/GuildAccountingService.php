<?php

namespace App\Services;

use App\Models\Character;
use App\Models\FinancialLedger;
use App\Models\GuildVault;
use App\Models\Quest;
use App\Models\QuestParticipant;
use Illuminate\Support\Facades\DB;

class GuildAccountingService
{
    /**
     * Record a corporate guild expense/withdrawal directly from vault.
     * character_id = null, entity_type = 'GUILD_RESERVE'
     */
    public function recordGuildExpense(
        int $guildVaultId,
        float $amountSp,
        string $description,
        ?int $vaultItemId = null
    ): FinancialLedger {
        return DB::transaction(function () use ($guildVaultId, $amountSp, $description, $vaultItemId) {
            $vault = GuildVault::lockForUpdate()->find($guildVaultId);
            if (!$vault) {
                $vault = GuildVault::create([
                    'name' => 'Main Guild Vault',
                    'balance_sp' => 0.00,
                ]);
            }

            $currentBalance = (float) $vault->balance_sp;
            $newBalance = round($currentBalance - abs($amountSp), 2);
            $vault->balance_sp = $newBalance;
            $vault->save();

            return FinancialLedger::create([
                'character_id' => null,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp' => -abs($amountSp),
                'balance_after_sp' => $newBalance,
                'transaction_type' => 'WITHDRAWAL',
                'reference_vault_item_id' => $vaultItemId,
                'description' => $description,
            ]);
        });
    }

    /**
     * Record guild income/deposit into vault.
     * character_id = null, entity_type = 'GUILD_RESERVE'
     */
    public function recordGuildIncome(
        int $guildVaultId,
        float $amountSp,
        string $description,
        string $transactionType = 'DEPOSIT',
        string $entityType = FinancialLedger::ENTITY_GUILD_RESERVE
    ): FinancialLedger {
        return DB::transaction(function () use ($guildVaultId, $amountSp, $description, $transactionType, $entityType) {
            $vault = GuildVault::lockForUpdate()->find($guildVaultId);
            if (!$vault) {
                $vault = GuildVault::create([
                    'name' => 'Main Guild Vault',
                    'balance_sp' => 0.00,
                ]);
            }

            $currentBalance = (float) $vault->balance_sp;
            $newBalance = round($currentBalance + abs($amountSp), 2);
            $vault->balance_sp = $newBalance;
            $vault->save();

            return FinancialLedger::create([
                'character_id' => null,
                'guild_vault_id' => $vault->id,
                'entity_type' => $entityType,
                'amount_sp' => abs($amountSp),
                'balance_after_sp' => $newBalance,
                'transaction_type' => $transactionType,
                'description' => $description,
            ]);
        });
    }

    /**
     * Record character allowance / share.
     * character_id = X, entity_type = 'CHARACTER', guild_vault_id = null
     */
    public function recordCharacterShare(
        int $characterId,
        float $amountSp,
        string $description = 'Quest share payout'
    ): FinancialLedger {
        return DB::transaction(function () use ($characterId, $amountSp, $description) {
            $character = Character::lockForUpdate()->findOrFail($characterId);
            $currentBalance = (float) ($character->allowance_sp ?? 0.00);
            $newBalance = round($currentBalance + $amountSp, 2);
            $character->allowance_sp = $newBalance;
            $character->save();

            return FinancialLedger::create([
                'character_id' => $characterId,
                'guild_vault_id' => null,
                'entity_type' => FinancialLedger::ENTITY_CHARACTER,
                'amount_sp' => round($amountSp, 2),
                'balance_after_sp' => $newBalance,
                'transaction_type' => 'QUEST_SHARE',
                'description' => $description,
            ]);
        });
    }

    /**
     * Process double-entry quest approval settlement:
     * - Each participant receives their share in their allowance (entity_type = 'CHARACTER', character_id = X).
     * - The 5% guild cut is credited directly to the guild vault (entity_type = 'GUILD_RESERVE', guild_vault_id = 1, character_id = NULL).
     * - All handled in a single database transaction.
     */
    public function processQuestSettlement(
        Quest $quest,
        array $characterShares = [],
        float $guildCutSp = 0.0,
        int $guildVaultId = 1
    ): array {
        return DB::transaction(function () use ($quest, $characterShares, $guildCutSp, $guildVaultId) {
            $createdEntries = [];

            // 1. Credit Guild cut to Guild Vault if applicable
            if ($guildCutSp > 0) {
                $guildEntry = $this->recordGuildIncome(
                    $guildVaultId,
                    $guildCutSp,
                    "Guild cut (5%) from Quest #{$quest->id} ({$quest->title})",
                    'DEPOSIT',
                    FinancialLedger::ENTITY_GUILD_RESERVE
                );
                $createdEntries['guild'] = $guildEntry;
            }

            // 2. Credit each participant's share to character allowance
            $participantEntries = [];
            foreach ($characterShares as $characterId => $amountSp) {
                if ($amountSp > 0) {
                    $entry = $this->recordCharacterShare(
                        (int) $characterId,
                        (float) $amountSp,
                        "Participant share from Quest #{$quest->id} ({$quest->title})"
                    );
                    $participantEntries[] = $entry;
                }
            }
            $createdEntries['characters'] = $participantEntries;

            // 3. Update Quest status
            $quest->board_status = 'APPROVED';
            $quest->status = 'COMPLETED';
            $quest->save();

            // 4. Mark accepted participants as COMPLETED
            $quest->participants()
                ->whereIn('attendance_status', [QuestParticipant::STATUS_ACCEPTED, QuestParticipant::STATUS_APPLIED])
                ->update(['attendance_status' => QuestParticipant::STATUS_COMPLETED]);

            return $createdEntries;
        });
    }
}
