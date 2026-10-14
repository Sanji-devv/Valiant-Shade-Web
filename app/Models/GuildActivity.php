<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class GuildActivity extends SpatieActivity
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function causer(): MorphTo
    {
        return $this->morphTo()->constrain([
            User::class => fn ($query) => $query->withTrashed(),
        ]);
    }
}
