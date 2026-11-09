<?php

namespace App\ValiantShade\Support;

final class GazetteTypography
{
    /**
     * Gazete yüzünde AI/İngilizce tipografi işareti sayılan uzun tireleri Türkçe noktalama ile değiştirir.
     */
    public static function scrub(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $normalized = str_replace(["\u{2014}", "\u{2013}", '—', '–'], ' · ', $text);
        $normalized = preg_replace('/\s+·\s+/u', ' · ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s{2,}/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function scrubIssuePayload(array $payload): array
    {
        foreach (['date_line', 'schedule', 'editorial_note', 'archive_label', 'label'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key])) {
                $payload[$key] = self::scrub($payload[$key]);
            }
        }

        if (isset($payload['corner_slots']) && is_array($payload['corner_slots'])) {
            $payload['corner_slots'] = array_map(static function ($slot) {
                if (! is_array($slot)) {
                    return $slot;
                }
                foreach (['title', 'body', 'teaser', 'rumuz'] as $field) {
                    if (isset($slot[$field]) && is_string($slot[$field])) {
                        $slot[$field] = self::scrub($slot[$field]);
                    }
                }

                return $slot;
            }, $payload['corner_slots']);
        }

        return $payload;
    }
}
