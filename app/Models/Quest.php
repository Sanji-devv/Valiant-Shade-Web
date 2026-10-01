<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    use HasFactory;

    protected $table = 'quests';

    protected $fillable = [
        'title',
        'quest_date',
        'is_main_quest',
        'level',
        'base_xp',
        'depo_fee_xp',
        'guild_cut_xp',
        'report_reward_xp',
        'bonus_pool_xp',
        'net_pool_xp',
        'per_player_xp',
        'gm_character_id',
        'reporter_character_id',
        'status',
        'board_status',
        'min_level',
        'max_level',
        'max_party_size',
        'application_deadline',
        'report_summary',
        'full_report',
    ];

    protected $casts = [
        'quest_date' => 'date',
        'is_main_quest' => 'boolean',
        'level' => 'integer',
        'base_xp' => 'float',
        'depo_fee_xp' => 'float',
        'guild_cut_xp' => 'float',
        'report_reward_xp' => 'float',
        'bonus_pool_xp' => 'float',
        'net_pool_xp' => 'float',
        'per_player_xp' => 'float',
        'min_level' => 'integer',
        'max_level' => 'integer',
        'max_party_size' => 'integer',
        'application_deadline' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Quest $quest) {
            $base = (float) ($quest->base_xp ?? 0);
            if (!isset($quest->guild_cut_xp) || $quest->guild_cut_xp == 0) {
                $quest->guild_cut_xp = round($base * 0.05, 2);
            }
            if (!isset($quest->report_reward_xp) || $quest->report_reward_xp == 0) {
                $quest->report_reward_xp = round($base * 0.10, 2);
            }
            if (!isset($quest->depo_fee_xp)) {
                $quest->depo_fee_xp = 0.00;
            }

            $bonus = (float) ($quest->bonus_pool_xp ?? 0);
            $deductions = (float) $quest->depo_fee_xp + (float) $quest->guild_cut_xp + (float) $quest->report_reward_xp;
            $quest->net_pool_xp = round(max(0, $base - $deductions + $bonus), 2);
        });
    }

    public function gm(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'gm_character_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'reporter_character_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(QuestParticipant::class, 'quest_id');
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'quest_participants')
            ->withPivot(['earned_quest_xp', 'earned_report_xp', 'is_kia', 'is_mia', 'attendance_status'])
            ->withTimestamps();
    }

    public function scopeRecruiting(Builder $query): Builder
    {
        return $query->where('board_status', 'RECRUITING');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('board_status', 'APPROVED');
    }
}