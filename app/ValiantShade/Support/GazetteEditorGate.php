<?php

namespace App\ValiantShade\Support;

use App\Models\User;

class GazetteEditorGate
{
    public static function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->hasPermission('archives.all') || $user->isArchivistBailiff()) {
            return true;
        }

        return $user->hasPermission('archives.create')
            || $user->hasPermission('archives.update');
    }
}
