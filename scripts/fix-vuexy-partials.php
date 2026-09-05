#!/usr/bin/env php
<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

function fixPartials(string $directory, string $prefix): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $updated = preg_replace(
            "/@include\\('_partials([\\.\\/][^']*)'/",
            "@include('{$prefix}._partials$1'",
            $content
        ) ?? $content;

        if ($updated !== $content) {
            file_put_contents($file->getPathname(), $updated);
        }
    }
}

fixPartials($projectRoot . '/resources/views/demo-vuexy', 'demo-vuexy');
fixPartials($projectRoot . '/resources/views/admin', 'admin');

echo "Partial include paths fixed.\n";
