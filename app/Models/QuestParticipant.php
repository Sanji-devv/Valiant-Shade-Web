<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestParticipant extends Model
{
    use HasFactory;

    protected $table = 'quest_participants';

    public const STATUS_APPLIED = 'APPLIED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'quest_id',
        'character_id',
        'attendance_status',
        'earned_quest_xp',
        'earned_report_xp',
        'is_kia',
        'is_mia',
    ];

    protected $casts = [
        'earned_quest_xp' => 'float',
        'earned_report_xp' => 'float',
        'is_kia' => 'boolean',
        'is_mia' => 'boolean',
    ];

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class, 'quest_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }
}