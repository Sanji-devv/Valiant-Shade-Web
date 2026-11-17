<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GodDomainOverride extends Model
{
    protected $primaryKey = 'slug';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['slug', 'alignment', 'domains', 'subdomains', 'weapon', 'category'];

    protected function casts(): array
    {
        return [
            'domains' => 'array',
            'subdomains' => 'array',
        ];
    }
}
