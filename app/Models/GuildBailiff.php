<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuildBailiff extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'guild_bailiffs';

    protected $fillable = [
        'character_name',
        'role',
        'assigned_date',
        'status',
        'order_index',
        'notes',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_RETIRED = 'retired';
}
