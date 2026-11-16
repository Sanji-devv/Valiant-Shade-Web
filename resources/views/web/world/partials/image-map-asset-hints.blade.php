@php
    $previewImage = $previewImage ?? ($config['image_preview'] ?? $config['image'] ?? null);
    $displayImage = $displayImage ?? ($config['image_display'] ?? null);
    $hdImage = $hdImage ?? ($config['image_hd'] ?? null);
@endphp
@if ($previewImage)
    <link rel="preload" href="{{ asset($previewImage) }}" as="image" fetchpriority="high">
@endif
@if ($displayImage)
    <link rel="prefetch" href="{{ asset($displayImage) }}" as="image" media="(min-width: 769px)">
@endif
@if ($hdImage)
    <link rel="prefetch" href="{{ asset($hdImage) }}" as="image" media="(min-width: 769px)">
@endif