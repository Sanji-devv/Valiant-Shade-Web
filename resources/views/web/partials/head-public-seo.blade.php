@php
    use App\Support\PublicSiteUrls;

    $siteName = (string) config('seo.site_name', 'Valiant Shade');
    $robots = $robots ?? 'index, follow';
    $sitemapUrl = PublicSiteUrls::main('/sitemap.xml');
    $llmsUrl = PublicSiteUrls::main('/llms.txt');
    $absoluteUrl = static function (?string $url): ?string {
        if ($url === null || $url === '') {
            return null;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    };
    $canonicalAbs = $absoluteUrl($canonical) ?? $canonical;
    $ogImageAbs = $absoluteUrl($ogImage ?? null);
@endphp
<meta name="robots" content="{{ $robots }}">
<meta name="description" content="{{ $description }}">
<meta name="application-name" content="{{ $siteName }}">
@if (! empty($keywords ?? null))
    <meta name="keywords" content="{{ $keywords }}">
@endif
<link rel="canonical" href="{{ $canonicalAbs }}">
<link rel="sitemap" type="application/xml" title="Sitemap" href="{{ $sitemapUrl }}">
<link rel="alternate" type="text/plain" title="LLMs.txt" href="{{ $llmsUrl }}">
<meta property="og:site_name" content="{{ $ogSiteName ?? $siteName }}">
<meta property="og:locale" content="tr_TR">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonicalAbs }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
@if (! empty($ogImageAbs))
    <meta property="og:image" content="{{ $ogImageAbs }}">
    <meta property="og:image:secure_url" content="{{ $ogImageAbs }}">
    @if (! empty($ogImageAlt ?? null))
        <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    @endif
@endif
<meta name="twitter:card" content="{{ $twitterCard ?? 'summary_large_image' }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
@if (! empty($ogImageAbs))
    <meta name="twitter:image" content="{{ $ogImageAbs }}">
@endif
