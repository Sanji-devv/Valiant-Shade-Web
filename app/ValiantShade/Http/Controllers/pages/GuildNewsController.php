<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\Models\News;
use App\ValiantShade\Http\Controllers\Controller;
use Database\Seeders\NewsSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuildNewsController extends Controller
{
    /**
     * Display a listing of guild news for management.
     */
    public function index(Request $request): View|JsonResponse
    {
        $news = News::orderBy('order', 'asc')->orderBy('created_at', 'desc')->get();

        $presets = [
            [
                'name' => 'Ejderha Zirvesi & Antik Harabeler',
                'path' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_dragon_quest.jpg'),
            ],
            [
                'name' => 'Lonca Hazinesi & Mahzen',
                'path' => 'static/valiant-shade/assets/img/news/guild_treasury_vault.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_treasury_vault.jpg'),
            ],
            [
                'name' => 'Gölge Diyarı & Obsidyen Hisar',
                'path' => 'static/valiant-shade/assets/img/news/guild_shadow_realm.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_shadow_realm.jpg'),
            ],
            [
                'name' => 'Lonca Meclisi & Büyük Salon',
                'path' => 'static/valiant-shade/assets/img/news/guild_hall_council.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_hall_council.jpg'),
            ],
            [
                'name' => 'Kadim Kütüphane & Büyü Kulesi',
                'path' => 'static/valiant-shade/assets/img/news/guild_arcane_academy.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_arcane_academy.jpg'),
            ],
            [
                'name' => 'Büyük Arena & Lonca Turnuvası',
                'path' => 'static/valiant-shade/assets/img/news/guild_arena_tournament.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_arena_tournament.jpg'),
            ],
            [
                'name' => 'Kutsal Ocak & Demirci Atölyesi',
                'path' => 'static/valiant-shade/assets/img/news/guild_forge_armory.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_forge_armory.jpg'),
            ],
            [
                'name' => 'Kadim Dehlizler & Yeraltı Zindanı',
                'path' => 'static/valiant-shade/assets/img/news/guild_dungeon_crypt.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_dungeon_crypt.jpg'),
            ],
            [
                'name' => 'Mistik Koruluk & Fısıldayan Orman',
                'path' => 'static/valiant-shade/assets/img/news/guild_mystic_forest.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_mystic_forest.jpg'),
            ],
            [
                'name' => 'Fırtına Limanı & Deniz Seferi',
                'path' => 'static/valiant-shade/assets/img/news/guild_port_expedition.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_port_expedition.jpg'),
            ],
            [
                'name' => 'Kutsal Mabet & Işık Sunağı',
                'path' => 'static/valiant-shade/assets/img/news/guild_celestial_sanctuary.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_celestial_sanctuary.jpg'),
            ],
            [
                'name' => 'Cenaze & Ölmüş Maceracıları Uğurlama',
                'path' => 'static/valiant-shade/assets/img/news/guild_heroes_funeral.jpg',
                'url' => asset('static/valiant-shade/assets/img/news/guild_heroes_funeral.jpg'),
            ],
        ];

        $iconOptions = [
            'icon-base ti tabler-trophy' => 'Kupa / Zafer',
            'icon-base ti tabler-coins' => 'Hazine / Altın',
            'icon-base ti tabler-flame' => 'Alev / Tehlike',
            'icon-base ti tabler-swords' => 'Kılıçlar / Sefer',
            'icon-base ti tabler-shield-check' => 'Kalkan / Güvenlik',
            'icon-base ti tabler-building-vault' => 'Mahzen / Kasa',
            'icon-base ti tabler-sparkles' => 'Parıltı / Kalıntı',
            'icon-base ti tabler-speakerphone' => 'Hoparlör / Duyuru',
            'icon-base ti tabler-scroll' => 'Parşömen / Ferman',
            'icon-base ti tabler-users-group' => 'Takım / Grup',
            'icon-base ti tabler-candle' => 'Mum / Anma',
            'icon-base ti tabler-coffin' => 'Tabut / Cenaze',
            'icon-base ti tabler-cross' => 'Anıt / Mezar',
            'icon-base ti tabler-clock' => 'Saat / Zaman',
            'icon-base ti tabler-calendar' => 'Takvim / Dönem',
            'icon-base ti tabler-map-pin' => 'Harita / Konum',
        ];

        $colorOptions = [
            'warning' => 'Sarı (Warning)',
            'success' => 'Yeşil (Success)',
            'danger' => 'Kırmızı (Danger)',
            'primary' => 'Mavi (Primary)',
            'info' => 'Açık Mavi (Info)',
            'secondary' => 'Gri (Secondary)',
            'dark' => 'Koyu (Dark)',
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'news' => $news,
            ]);
        }

        return view('valiant-shade.content.pages.pages-guild-news', compact(
            'news',
            'presets',
            'iconOptions',
            'colorOptions'
        ));
    }

    /**
     * Store a newly created guild news item.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'badge_color' => 'required|string|in:warning,success,danger,primary,info,secondary,dark',
            'category_icon' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:100',
            'tag_icon' => 'nullable|string|max:100',
            'date_text' => 'nullable|string|max:100',
            'description' => 'required|string',
            'image_preset' => 'nullable|string',
            'custom_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'button_color' => 'required|string|in:warning,success,danger,primary,info,secondary,dark',
            'button_icon' => 'nullable|string|max:100',
            'location_badge' => 'nullable|string|max:150',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'stats' => 'nullable|array',
            'stats.*.label' => 'nullable|string|max:50',
            'stats.*.value' => 'nullable|string|max:50',
            'stats.*.color' => 'nullable|string|max:50',
        ]);

        // Handle image
        $imageUrl = 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg';
        if ($request->hasFile('custom_image')) {
            $file = $request->file('custom_image');
            $filename = 'news_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('static/valiant-shade/assets/img/news');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imageUrl = 'static/valiant-shade/assets/img/news/' . $filename;
        } elseif (!empty($request->image_preset)) {
            $imageUrl = $request->image_preset;
        }

        // Process stats
        $stats = $this->sanitizeStats($request->input('stats', []));

        $news = News::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'badge_color' => $validated['badge_color'],
            'category_icon' => !empty($validated['category_icon']) ? $validated['category_icon'] : 'icon-base ti tabler-trophy',
            'tag' => $validated['tag'] ?? null,
            'tag_icon' => !empty($validated['tag_icon']) ? $validated['tag_icon'] : 'icon-base ti tabler-sparkles',
            'date_text' => $validated['date_text'] ?? null,
            'description' => $validated['description'],
            'image_url' => $imageUrl,
            'stats' => $stats,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'button_color' => $validated['button_color'] ?? 'primary',
            'button_icon' => !empty($validated['button_icon']) ? $validated['button_icon'] : 'icon-base ti tabler-shield-check',
            'location_badge' => $validated['location_badge'] ?? null,
            'order' => $validated['order'] ?? (News::max('order') + 1),
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Haber ve bülten slaytı başarıyla eklendi.',
                'news' => $news,
            ]);
        }

        return redirect()->route('valiant-shade.pages-guild-news')->with('success', 'Haber ve bülten slaytı başarıyla eklendi.');
    }

    /**
     * Update the specified guild news item.
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $news = News::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'badge_color' => 'required|string|in:warning,success,danger,primary,info,secondary,dark',
            'category_icon' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:100',
            'tag_icon' => 'nullable|string|max:100',
            'date_text' => 'nullable|string|max:100',
            'description' => 'required|string',
            'image_preset' => 'nullable|string',
            'custom_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'button_color' => 'required|string|in:warning,success,danger,primary,info,secondary,dark',
            'button_icon' => 'nullable|string|max:100',
            'location_badge' => 'nullable|string|max:150',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'stats' => 'nullable|array',
            'stats.*.label' => 'nullable|string|max:50',
            'stats.*.value' => 'nullable|string|max:50',
            'stats.*.color' => 'nullable|string|max:50',
        ]);

        // Handle image update if provided
        $imageUrl = $news->image_url;
        if ($request->hasFile('custom_image')) {
            $file = $request->file('custom_image');
            $filename = 'news_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('static/valiant-shade/assets/img/news');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imageUrl = 'static/valiant-shade/assets/img/news/' . $filename;
        } elseif (!empty($request->image_preset)) {
            $imageUrl = $request->image_preset;
        }

        // Process stats
        $stats = $this->sanitizeStats($request->input('stats', []));

        $news->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'badge_color' => $validated['badge_color'],
            'category_icon' => !empty($validated['category_icon']) ? $validated['category_icon'] : 'icon-base ti tabler-trophy',
            'tag' => $validated['tag'] ?? null,
            'tag_icon' => !empty($validated['tag_icon']) ? $validated['tag_icon'] : 'icon-base ti tabler-sparkles',
            'date_text' => $validated['date_text'] ?? null,
            'description' => $validated['description'],
            'image_url' => $imageUrl,
            'stats' => $stats,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'button_color' => $validated['button_color'] ?? 'primary',
            'button_icon' => !empty($validated['button_icon']) ? $validated['button_icon'] : 'icon-base ti tabler-shield-check',
            'location_badge' => $validated['location_badge'] ?? null,
            'order' => $validated['order'] ?? $news->order,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : $news->is_active,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Haber ve bülten slaytı başarıyla güncellendi.',
                'news' => $news,
            ]);
        }

        return redirect()->route('valiant-shade.pages-guild-news')->with('success', 'Haber ve bülten slaytı başarıyla güncellendi.');
    }

    /**
     * Delete the specified guild news item.
     */
    public function destroy(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $news = News::findOrFail($id);
        $news->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Haber ve bülten slaytı silindi.',
            ]);
        }

        return redirect()->route('valiant-shade.pages-guild-news')->with('success', 'Haber ve bülten slaytı silindi.');
    }

    /**
     * Toggle the active status of a news item.
     */
    public function toggleActive(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $news = News::findOrFail($id);
        $news->is_active = !$news->is_active;
        $news->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $news->is_active,
                'message' => $news->is_active ? 'Haber yayına alındı.' : 'Haber taslağa çekildi.',
            ]);
        }

        return redirect()->route('valiant-shade.pages-guild-news')->with('success', 'Durum güncellendi.');
    }

    /**
     * Reset news to default 3 fantasy slides.
     */
    public function resetDefaults(Request $request): RedirectResponse|JsonResponse
    {
        News::truncate();
        $seeder = new NewsSeeder();
        $seeder->run();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Varsayılan lonca haberleri ve sefer raporları geri yüklendi.',
            ]);
        }

        return redirect()->route('valiant-shade.pages-guild-news')->with('success', 'Varsayılan lonca haberleri ve sefer raporları geri yüklendi.');
    }

    /**
     * Sanitize and format stats array.
     */
    protected function sanitizeStats(array $rawStats): array
    {
        $clean = [];
        foreach ($rawStats as $item) {
            if (!empty($item['label']) || !empty($item['value'])) {
                $clean[] = [
                    'label' => $item['label'] ?? '',
                    'value' => $item['value'] ?? '',
                    'color' => $item['color'] ?? 'text-white',
                ];
            }
        }
        return array_slice($clean, 0, 4);
    }
}
