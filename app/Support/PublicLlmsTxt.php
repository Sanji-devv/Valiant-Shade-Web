<?php

namespace App\Support;

use App\Models\News;

final class PublicLlmsTxt
{
    public static function summary(): string
    {
        $site = (string) config('seo.site_name', 'Valiant Shade');
        $tagline = (string) config('seo.home.description');
        $gazette = (string) config('gazette.title', 'Lağımdan Sesler');

        $lines = [
            '# '.$site,
            '',
            '> '.$tagline,
            '',
            'Bu dosya, yapay zeka sistemlerinin Valiant Shade halka açık yüzünü özetler.',
            'Öncelik dili: Türkçe (`tr`). Evren: Pathfinder 1e tabanlı lonca kampanyası (Mineria).',
            '',
            '## Öne çıkan sayfalar',
            '',
            '- [Ana portal]('.self::link('/').'): Gazete, harita, canavar veritabanı ve lonca kayıtlarına giriş.',
            '- ['.$gazette.' gazetesi]('.self::link(PublicSiteUrls::gazette('/')).'): Halka açık haberler, söylentiler ve köşe yazıları.',
            '- [Canavar veritabanı]('.self::link(PublicSiteUrls::monsterDatabase('/')).'): Yaratık kayıtları (okuma).',
            '- [Dünya haritası — Feldan]('.self::link(PublicSiteUrls::map('/feldan')).'): Krallık haritası (okuma).',
            '- [Lonca kayıtları]('.self::link('/valiant-shade').'): Lonca arayüzü (beta).',
            '',
            '## Dizin ve politika',
            '',
            '- [XML site haritası]('.self::link('/sitemap.xml').'): Tüm halka açık URL’ler (gazete haberleri dahil).',
            '- [robots.txt]('.self::link('/robots.txt').'): Tarayıcı kuralları.',
            '- [Genişletilmiş llms-full.txt]('.self::link('/llms-full.txt').'): Son yayınlanan haber bağlantıları.',
            '',
            '## Dizine alınmaması gerekenler',
            '',
            '- Üye girişi sonrası yönetim ve karakter ekranları.',
            '- `/api/` altındaki JSON uçları.',
            '- Harita düzenleme (`/world/*/edit`) ve dahili araçlar.',
            '',
        ];

        $contact = trim((string) config('seo.llms.contact', ''));
        if ($contact !== '') {
            $lines[] = '## İletişim';
            $lines[] = '';
            $lines[] = '- '.$contact;
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public static function full(): string
    {
        $body = self::summary();
        $body .= "## Son yayınlanan haberler (örnek)\n\n";
        $body .= 'Tam liste için site haritasına bakın: '.self::link('/sitemap.xml')."\n\n";

        $articles = News::query()
            ->where('status', 'published')
            ->orderByDesc('updated_at')
            ->limit((int) config('seo.llms.full_article_limit', 40))
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get(['slug', 'title', 'updated_at']);

        if ($articles->isEmpty()) {
            $body .= "- (Henüz yayınlanmış haber yok.)\n";

            return $body;
        }

        foreach ($articles as $news) {
            $title = trim(str_replace(["\n", "\r"], ' ', (string) $news->title));
            $url = PublicSiteUrls::gazetteArticle((string) $news->slug);
            $body .= '- ['.$title.']('.self::link($url).")\n";
        }

        return $body."\n";
    }

    private static function link(string $urlOrPath): string
    {
        if (str_starts_with($urlOrPath, 'http://') || str_starts_with($urlOrPath, 'https://')) {
            return $urlOrPath;
        }

        if ($urlOrPath === '/' || $urlOrPath === '') {
            return PublicSiteUrls::main('/');
        }

        return PublicSiteUrls::main($urlOrPath);
    }
}