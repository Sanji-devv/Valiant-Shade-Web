<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathfinderTrait extends Model
{
    use HasFactory;

    protected $table = 'traits';

    protected $fillable = [
        'name',
        'category',
        'description',
        'source_url',
    ];
}
