#!/usr/bin/env php
<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

function transformVitePaths(string $content, string $resourcePrefix, string $buildDirectory): string
{
    $content = str_replace('resources/assets/', $resourcePrefix . '/assets/', $content);
    $content = str_replace('resources/css/', $resourcePrefix . '/css/', $content);
    $content = str_replace('resources/js/', $resourcePrefix . '/js/', $content);

    $content = preg_replace_callback(
        '/@vite\(\[(.*?)\]\)/s',
        static function (array $matches) use ($buildDirectory): string {
            $entries = trim($matches[1]);

            if (str_contains($entries, "'{$buildDirectory}'") || str_contains($entries, "\"{$buildDirectory}\"")) {
                return $matches[0];
            }

            return "@vite([{$entries}], '{$buildDirectory}')";
        },
        $content
    ) ?? $content;

    $content = preg_replace_callback(
        "/@vite\\('([^']+)'\\)/",
        static function (array $matches) use ($buildDirectory): string {
            if (str_contains($matches[0], "'{$buildDirectory}'")) {
                return $matches[0];
            }

            return "@vite('{$matches[1]}', '{$buildDirectory}')";
        },
        $content
    ) ?? $content;

    return $content;
}

function processBladeDirectory(string $directory, string $resourcePrefix, string $buildDirectory): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $updated = transformVitePaths($content, $resourcePrefix, $buildDirectory);

        if ($updated !== $content) {
            file_put_contents($file->getPathname(), $updated);
        }
    }
}

echo "Updating demo-vuexy vite paths...\n";
processBladeDirectory(
    $projectRoot . '/resources/views/demo-vuexy',
    'resources/demo-vuexy',
    'static/demo-vuexy/build'
);

echo "Updating admin vite paths...\n";
processBladeDirectory(
    $projectRoot . '/resources/views/admin',
    'resources/admin',
    'static/admin/build'
);

echo "Vite path updates complete.\n";
