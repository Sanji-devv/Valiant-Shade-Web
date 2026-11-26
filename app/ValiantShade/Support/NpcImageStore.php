<?php

namespace App\ValiantShade\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class NpcImageStore
{
    public const DISK = 'public';

    public const DIRECTORY = 'npcs';

    /**
     * Store an uploaded NPC portrait on the public disk and return a site-relative path.
     */
    public static function store(UploadedFile $file, string $prefix = 'npc'): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }

        $filename = sprintf(
            '%s_%s_%s.%s',
            preg_replace('/[^a-z0-9_-]+/i', '', $prefix) ?: 'npc',
            now()->format('Ymd_His'),
            Str::lower(Str::random(10)),
            $ext,
        );

        $stored = $file->storeAs(self::DIRECTORY, $filename, self::DISK);
        if ($stored === false || $stored === '') {
            throw new RuntimeException('NPC portrait could not be saved.');
        }

        return 'storage/'.$stored;
    }

    /**
     * Delete a previously stored NPC portrait if it lives in the managed NPC directory.
     */
    public static function delete(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        $prefix = 'storage/'.self::DIRECTORY.'/';
        $normalized = ltrim($relativePath, '/');
        if (! str_starts_with($normalized, $prefix)) {
            return;
        }

        $diskPath = substr($normalized, strlen('storage/'));
        if ($diskPath !== '' && Storage::disk(self::DISK)->exists($diskPath)) {
            Storage::disk(self::DISK)->delete($diskPath);
        }
    }
}
