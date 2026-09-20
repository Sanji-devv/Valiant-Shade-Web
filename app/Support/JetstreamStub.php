<?php

namespace App\Support;

class JetstreamStub
{
    public static function hasApiFeatures(): bool
    {
        return false;
    }

    public static function hasTeamFeatures(): bool
    {
        return false;
    }

    public static function newTeamModel(): ?string
    {
        return null;
    }

    public static function hasAccountDeletionFeatures(): bool
    {
        return false;
    }

    public static function hasTermsAndPrivacyPolicyFeature(): bool
    {
        return false;
    }

    public static function managesProfilePhotos(): bool
    {
        return false;
    }
}
