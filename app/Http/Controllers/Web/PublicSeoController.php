<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PublicLlmsTxt;
use App\Support\PublicSiteUrls;
use App\Support\PublicSitemap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicSeoController extends Controller
{
    public function sitemap(Request $request): RedirectResponse|Response
    {
        $mainHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($mainHost) && $mainHost !== '' && $request->getHost() !== $mainHost) {
            return redirect()->away(PublicSiteUrls::main('/sitemap.xml'), 301);
        }

        return response()
            ->view('web.sitemap', ['urls' => PublicSitemap::urls()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /api/',
            'Disallow: /world/*/edit',
            '',
            'Sitemap: '.PublicSiteUrls::main('/sitemap.xml'),
            '',
            '# LLM özeti: '.PublicSiteUrls::main('/llms.txt'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function llms(Request $request): RedirectResponse|Response
    {
        return $this->plainTextOnMainHost($request, '/llms.txt', PublicLlmsTxt::summary());
    }

    public function llmsFull(Request $request): RedirectResponse|Response
    {
        return $this->plainTextOnMainHost($request, '/llms-full.txt', PublicLlmsTxt::full());
    }

    private function plainTextOnMainHost(Request $request, string $path, string $body): RedirectResponse|Response
    {
        $mainHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($mainHost) && $mainHost !== '' && $request->getHost() !== $mainHost) {
            return redirect()->away(PublicSiteUrls::main($path), 301);
        }

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}