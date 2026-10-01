<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterGraveyard extends Model
{
    use HasFactory;

    protected $table = 'character_graveyard';

    protected $fillable = [
        'user_id',
        'character_id',
        'cause',
        'quest_count_at_death',
        'quest_xp_at_death',
        'report_xp_at_death',
        'transferred_xp',
        'death_quest_id',
        'recorded_at',
    ];

    protected $casts = [
        'quest_count_at_death' => 'integer',
        'quest_xp_at_death' => 'float',
        'report_xp_at_death' => 'float',
        'transferred_xp' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function deathQuest(): BelongsTo
    {
        return $this->belongsTo(Quest::class, 'death_quest_id');
    }

    /**
     * Miras Kuralı:
     * ex_char_xp = IF(char_type == 'CLERK', 0, SUM(transferred_xp WHERE user_id == X))
     */
    public static function calculateExCharXpForUser(int $userId, string $charType = 'MAIN'): float
    {
        if (strtoupper($charType) === 'CLERK') {
            return 0.00;
        }

        return (float) static::where('user_id', $userId)->sum('transferred_xp');
    }
}