<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionRoute extends Model
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
