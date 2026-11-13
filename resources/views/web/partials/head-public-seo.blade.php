@php
    $siteName = (string) config('seo.site_name', 'Valiant Shade');
    $robots = $robots ?? 'index, follow';
@endphp
<meta name="robots" content="{{ $robots }}">
<meta name="description" content="{{ $description }}">
@if (! empty($keywords ?? null))
    <meta name="keywords" content="{{ $keywords }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:site_name" content="{{ $ogSiteName ?? $siteName }}">
<meta property="og:locale" content="tr_TR">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
@if (! empty($ogImage ?? null))
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    @if (! empty($ogImageAlt ?? null))
        <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    @endif
@endif
<meta name="twitter:card" content="{{ $twitterCard ?? 'summary_large_image' }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
@if (! empty($ogImage ?? null))
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif
