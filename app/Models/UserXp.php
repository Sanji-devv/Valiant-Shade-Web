<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserXp extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_xp';

    protected $fillable = [
        'user_id',
        'total_xp',
        'ex_char_xp',
        'ex_bailiff_xp',
        'wiki_xp',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'total_xp' => 'decimal:2',
        'ex_char_xp' => 'decimal:2',
        'ex_bailiff_xp' => 'decimal:2',
        'wiki_xp' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
