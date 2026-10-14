<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PathfinderTrait extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'traits';

    protected $fillable = [
        'name',
        'category',
        'description',
        'external_url',
    ];

    public function getSourceUrlAttribute(): ?string
    {
        return $this->attributes['external_url'] ?? null;
    }

    public function setSourceUrlAttribute($value): void
    {
        $this->attributes['external_url'] = $value;
    }
}
