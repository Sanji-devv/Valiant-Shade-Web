<?php

namespace App\ValiantShade\Support;

use App\Models\News;
use Illuminate\Support\Str;

final class NewsSlug
{
    public static function fromTitle(string $title, ?int $fallbackId = null): string
    {
        $slug = Str::slug(self::transliterate($title), '-', 'tr');
        if ($slug === '') {
            $slug = $fallbackId ? 'haber-'.$fallbackId : 'haber';
        }

        return Str::limit($slug, 96, '');
    }

    public static function assignUnique(string $title, ?int $exceptId = null): string
    {
        $base = self::fromTitle($title, $exceptId);
        $slug = $base;
        $suffix = 2;

        while (self::exists($slug, $exceptId)) {
            $slug = Str::limit($base, 90, '').'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function exists(string $slug, ?int $exceptId = null): bool
    {
        $query = News::withTrashed()->where('slug', $slug);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public static function isValidFormat(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?$/', $slug);
    }

    private static function transliterate(string $text): string
    {
        $map = [
            'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u',
            'ş' => 's', 'Ş' => 's',
            'ı' => 'i', 'İ' => 'i',
            'ö' => 'o', 'Ö' => 'o',
            'ç' => 'c', 'Ç' => 'c',
        ];

        return strtr($text, $map);
    }
}