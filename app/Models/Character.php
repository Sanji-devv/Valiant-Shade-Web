<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Character extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'characters';

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'character_type',
        'status',
        'rank_id',
        'current_level',
        'allowance_sp',
        'total_xp',
        'rank_floor_xp',
        'quest_xp',
        'report_xp',
        'bailiff_xp',
        'ex_char_xp',
        'wiki_xp',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'current_level' => 'integer',
        'allowance_sp' => 'float',
        'total_xp' => 'float',
        'rank_floor_xp' => 'float',
        'quest_xp' => 'float',
        'report_xp' => 'float',
        'bailiff_xp' => 'float',
        'ex_char_xp' => 'float',
        'wiki_xp' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (Character $char) {
            $subTotal = (float) ($char->rank_floor_xp ?? 0) +
                (float) ($char->quest_xp ?? 0) +
                (float) ($char->report_xp ?? 0) +
                (float) ($char->bailiff_xp ?? 0) +
                (float) ($char->ex_char_xp ?? 0) +
                (float) ($char->wiki_xp ?? 0);

            if ($subTotal > 0 || (float) ($char->total_xp ?? 0) == 0) {
                $char->total_xp = round($subTotal, 2);
            }

            // Dynamically recalculate level
            if ($char->total_xp > 0) {
                $char->current_level = LevelThreshold::calculateLevelFromXp($char->total_xp);
            } elseif (empty($char->current_level)) {
                $char->current_level = 1;
            }

            // Dynamically recalculate guild rank for standard non-clerk characters
            if (strtoupper($char->character_type ?? '') !== 'CLERK' && !in_array((int)$char->rank_id, [2, 6, 7, 8], true)) {
                $sessionsCount = 0;
                if ($char->exists) {
                    $sessionsCount = $char->questParticipants()->count();
                }
                if ($sessionsCount === 0 && !empty($char->name)) {
                    $sessionsCount = (int) (\App\Models\PlayerXp::where('character_name', $char->name)->value('sessions_count') ?? 0);
                }
                $rankName = self::calculateRankFromLevelAndSessions((int)$char->current_level, $sessionsCount);
                $rankModel = Rank::where('name', $rankName)->first();
                if ($rankModel) {
                    $char->rank_id = $rankModel->id;
                }
            }
        });
    }

    /**
     * Calculate guild rank strictly based on level and games played (sessions count):
     * - Level 1: Aday
     * - Level 2-5: Üye
     * - Level 6-9: Kıdemli
     * - Level 10: If sessions_count >= 3 => Uzman; otherwise Kıdemli
     * - Level 11+: Uzman
     */
    public static function calculateRankFromLevelAndSessions(int $level, int $sessionsCount = 0): string
    {
        if ($level <= 1) {
            return 'Aday';
        }
        if ($level >= 2 && $level <= 5) {
            return 'Üye';
        }
        if ($level >= 6 && $level <= 9) {
            return 'Kıdemli';
        }
        if ($level === 10) {
            return $sessionsCount >= 3 ? 'Uzman' : 'Kıdemli';
        }
        return 'Uzman';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Character::class, 'parent_id');
    }

    public function xpRecord(): HasOne
    {
        return $this->hasOne(CharacterXp::class, 'character_id');
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'rank_id');
    }

    public function info(): HasOne
    {
        return $this->hasOne(CharacterInfo::class, 'character_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(CharacterProfile::class, 'character_id');
    }

    public function traits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CharacterTrait::class, 'character_id');
    }

    public function feats(): HasMany
    {
        return $this->hasMany(CharacterFeat::class, 'character_id');
    }

    public function catalogFeats(): BelongsToMany
    {
        return $this->belongsToMany(Feat::class, 'character_feats', 'character_id', 'feat_id')
            ->withPivot(['custom_feat_name', 'acquired_at_level', 'slot_type', 'notes'])
            ->withTimestamps();
    }

    public function questParticipants(): HasMany
    {
        return $this->hasMany(QuestParticipant::class, 'character_id');
    }

    public function quests(): BelongsToMany
    {
        return $this->belongsToMany(Quest::class, 'quest_participants')
            ->withPivot(['earned_quest_xp', 'earned_report_xp', 'is_kia', 'is_mia', 'attendance_status'])
            ->withTimestamps();
    }

    public function activeQuests(): BelongsToMany
    {
        return $this->belongsToMany(Quest::class, 'quest_participants')
            ->whereIn('quests.board_status', ['RECRUITING', 'IN_PROGRESS'])
            ->withPivot(['earned_quest_xp', 'earned_report_xp', 'is_kia', 'is_mia', 'attendance_status'])
            ->withTimestamps();
    }

    public function graveyardRecord(): HasOne
    {
        return $this->hasOne(CharacterGraveyard::class, 'character_id');
    }

    public function xpLedgerEntries(): HasMany
    {
        return $this->hasMany(XpLedger::class, 'character_id');
    }

    public function masteredQuests(): HasMany
    {
        return $this->hasMany(Quest::class, 'gm_character_id');
    }

    public function reportedQuests(): HasMany
    {
        return $this->hasMany(Quest::class, 'reporter_character_id');
    }

    public function borrowedVaultItems(): HasMany
    {
        return $this->hasMany(GuildVaultItem::class, 'borrowed_by_character_id');
    }

    public function custodianAssets(): HasMany
    {
        return $this->hasMany(GuildPermanentAsset::class, 'custodian_character_id');
    }

    public function financialLedgerEntries(): HasMany
    {
        return $this->hasMany(FinancialLedger::class, 'character_id');
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialLedger::class, 'character_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'character_id');
    }

    public function inventoryAssignments(): HasMany
    {
        return $this->hasMany(CharacterInventoryAssignment::class, 'character_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeFallen(Builder $query): Builder
    {
        return $query->whereIn('status', ['KIA', 'MIA']);
    }

    public function scopeMain(Builder $query): Builder
    {
        return $query->where('character_type', 'MAIN');
    }

    public function scopeClerk(Builder $query): Builder
    {
        return $query->where('character_type', 'CLERK');
    }

    /**
     * Recalculate total XP from active columns and update current_level.
     */
    public function recalculateTotalXp(): self
    {
        $this->total_xp = round(
            (float) $this->rank_floor_xp +
            (float) $this->quest_xp +
            (float) $this->report_xp +
            (float) $this->bailiff_xp +
            (float) $this->ex_char_xp +
            (float) $this->wiki_xp,
            2
        );
        $this->current_level = LevelThreshold::calculateLevelFromXp($this->total_xp);
        $this->save();
        return $this;
    }
}
