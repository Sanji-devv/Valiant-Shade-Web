<?php

namespace App\ValiantShade\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GazetteSubmissionImageStore
{
    public const DISK = 'public';

    public const DIRECTORY = 'gazette-submissions';

    /**
     * Returns a site-relative path suitable for {@see News::$image_url} (via asset()).
     */
    public static function store(UploadedFile $file, string $prefix = 'sub'): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }

        $filename = sprintf(
            '%s_%s_%s.%s',
            preg_replace('/[^a-z0-9_-]+/i', '', $prefix) ?: 'sub',
            now()->format('Ymd_His'),
            Str::lower(Str::random(10)),
            $ext,
        );

        $stored = $file->storeAs(self::DIRECTORY, $filename, self::DISK);
        if ($stored === false || $stored === '') {
            throw new RuntimeException('Görsel sunucuya yazılamadı. storage:link ve yazma izinlerini kontrol edin.');
        }

        return 'storage/'.$stored;
    }

    public static function resolveUploadedOrPreset(
        ?UploadedFile $customFile,
        ?string $imagePreset,
        string $defaultRelativePath,
        string $prefix = 'sub',
    ): string {
        if ($customFile) {
            return self::store($customFile, $prefix);
        }

        $preset = trim((string) $imagePreset);
        if ($preset !== '' && $preset !== '__gazette_auto__') {
            return ltrim($preset, '/');
        }

        return ltrim($defaultRelativePath, '/');
    }

    public static function ensureDirectoryExists(): void
    {
        Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);
    }
}
