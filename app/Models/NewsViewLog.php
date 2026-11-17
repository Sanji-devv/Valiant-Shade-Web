<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsViewLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'news_id',
        'ip_address',
        'client_port',
        'counted',
        'user_agent',
    ];

    protected $casts = [
        'counted' => 'boolean',
        'client_port' => 'integer',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}