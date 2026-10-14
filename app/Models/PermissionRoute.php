<?php

namespace App\Models;

use App\Models\BaseModel;

class PermissionRoute extends BaseModel
{
    protected $table = 'routes';

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'badge',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
