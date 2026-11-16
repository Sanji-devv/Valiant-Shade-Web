<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestReportXpEntry extends BaseModel
{
    protected $fillable = [
        'quest_id', 'character_id', 'player_xp_id', 'participant_name', 'participant_key',
        'quest_xp', 'report_xp', 'state',
    ];

    protected $casts = [
        'quest_xp' => 'decimal:4',
        'report_xp' => 'decimal:4',
    ];

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function playerXp(): BelongsTo
    {
        return $this->belongsTo(PlayerXp::class)->withTrashed();
    }
}
