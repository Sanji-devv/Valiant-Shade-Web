<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\ValiantShade\Services\PublicNewsService;
use App\ValiantShade\Support\WorldCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PublicNewsController extends Controller
{
    public function __construct(
        private PublicNewsService $news,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(24, max(3, (int) $request->query('per_page', 9)));
        $newsType = $request->query('type');
        $search = $request->query('q');

        if ($newsType && ! isset(config('news.types')[$newsType])) {
            $newsType = null;
        }

        $payload = $this->news->bootstrap($page, $perPage, $newsType, $search);

        return $this->cachedJson([
            'success' => true,
            ...$payload,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(24, max(3, (int) $request->query('per_page', 9)));
        $newsType = $request->query('type');
        $search = $request->query('q');

        if ($newsType && ! isset(config('news.types')[$newsType])) {
            $newsType = null;
        }

        return $this->cachedJson([
            'success' => true,
            ...$this->news->paginate($page, $perPage, $newsType, $search),
        ]);
    }

    public function featured(Request $request): JsonResponse
    {
        $limit = min(10, max(1, (int) $request->query('limit', 5)));

        return $this->cachedJson([
            'success' => true,
            'data' => $this->news->featured($limit),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->news->findPublished($id);
        if (! $item) {
            return response()->json(['success' => false, 'message' => __('News not found.')], 404);
        }

        return $this->cachedJson([
            'success' => true,
            'news' => $item,
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:8|max:255',
            'news_type' => 'required|string|in:'.implode(',', array_keys(config('news.types', []))),
            'description' => 'required|string|min:30|max:5000',
            'location_badge' => 'nullable|string|max:150',
            'author' => 'nullable|string|max:120',
            'anonymous' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Please check the rumor form fields.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $typeConfig = config('news.types.'.$data['news_type']);

        $author = ! empty($data['anonymous']) ? 'Anonim Gözcü' : ($data['author'] ?? 'Sokak Muhabiri');

        $news = News::create([
            'title' => $data['title'],
            'category' => $typeConfig['label'],
            'news_type' => $data['news_type'],
            'badge_color' => $typeConfig['badge_color'],
            'category_icon' => $typeConfig['category_icon'],
            'tag' => 'Söylenti Gönderisi',
            'tag_icon' => 'icon-base ti tabler-message-2-share',
            'date_text' => WorldCalendar::formatNewsStamp(now()) ?? now()->locale(app()->getLocale())->translatedFormat('d M Y, H:i'),
            'description' => $data['description'],
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'location_badge' => $data['location_badge'] ?? null,
            'author' => $author,
            'author_role' => 'Halktan Gelen İhbar',
            'status' => 'pending',
            'is_active' => false,
            'order' => (int) News::max('order') + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Your rumor was delivered to the editors. It will appear after review.'),
            'submission_id' => $news->id,
        ], 201);
    }

    protected function cachedJson(array $payload, int $status = 200): JsonResponse
    {
        $version = (int) Cache::get('public.news.cache_version', 1);

        return response()
            ->json($payload, $status)
            ->header('Cache-Control', 'private, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('X-Public-News-Cache-Version', (string) $version);
    }
}
