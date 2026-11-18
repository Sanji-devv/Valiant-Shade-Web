<?php

namespace App\ValiantShade\Support;

use App\Models\News;
use Illuminate\Support\Facades\Schema;

final class NewsPublication
{
    public const LAGIMDAN = 'lagimdan_sesler';

    public const GNOME_CIGLISI = 'gnome_ciglisi';

    public static function isGnome(object $news): bool
    {
        return self::normalize((string) ($news->publication ?? self::LAGIMDAN)) === self::GNOME_CIGLISI;
    }

    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, [self::LAGIMDAN, self::GNOME_CIGLISI], true)
            ? $value
            : self::LAGIMDAN;
    }

    public static function columnExists(): bool
    {
        return Schema::hasColumn('news', 'publication');
    }
}