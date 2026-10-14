<?php

namespace App\Models;

use App\Models\Concerns\LogsGuildActivity;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use LogsGuildActivity;
}
