<?php

namespace App\Models;

use App\Models\Concerns\LogsGuildActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use LogsGuildActivity;
}
