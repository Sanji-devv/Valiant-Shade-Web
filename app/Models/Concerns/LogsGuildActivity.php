<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsGuildActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept([
                'password',
                'password_hash',
                'remember_token',
                'created_at',
                'updated_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('model');
    }
}
