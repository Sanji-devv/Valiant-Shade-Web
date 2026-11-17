<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GazetteIssue;
use App\Models\News;
use App\ValiantShade\Support\GazetteEditorGate;
use App\ValiantShade\Support\GazetteNewsImage;
use App\ValiantShade\Support\GazetteSubmissionImageStore;
use App\ValiantShade\Support\NewsEngagement;
use App\ValiantShade\Services\GazetteIssueService;
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
        private GazetteIssueService $gazetteIssues,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $newsType = $request->query('type');
        $search = $request->query('q');

        if ($newsType && ! isset(config('news.types')[$newsType])) {
            $newsType = null;
        }

        if ($request->boolean('gazette_layout')) {
            $sheet = max(1, (int) $request->query('sheet', $request->query('page', 1)));
            $issueId = $request->query('issue_id') ? (int) $request->query('issue_id') : null;

            $payload = $this->gazetteIssues->gazetteBootstrap($sheet, $issueId, $newsType, $search);

            return $this->cachedJson([
                'success' => true,
                ...$payload,
            ]);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(24, max(3, (int) $request->query('per_page', 9)));

        $payload = $this->news->bootstrap($page, $perPage, $newsType, $search);

        return $this->cachedJson([
            'success' => true,
            ...$payload,
        ]);
    }

    public function gazetteIssues(): JsonResponse
    {
        $current = $this->gazetteIssues->ensureDefaultIssue();

        return $this->cachedJson([
            'success' => true,
            'current_issue_id' => $current->id,
            'data' => $this->gazetteIssues->archiveList(),
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

        $payload = $request->boolean('stream')
            ? $this->news->paginateStream($page, $perPage, $search)
            : $this->news->paginate($page, $perPage, $newsType, $search);

        return $this->cachedJson([
            'success' => true,
            ...$payload,
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

    public function show(Request $request, int $id): JsonResponse
    {
        $model = $this->news->findPublishedForShow($id);

        if (! $model) {
            return response()->json(['success' => false, 'message' => 'Haber bulunamadı.'], 404);
        }

        try {
            NewsEngagement::recordView($model, $request);
        } catch (\Throwable) {
            // Okunma logu başarısız olsa da haber metni sunulsun.
        }

        return response()->json([
            'success' => true,
            'news' => $this->news->transformItem($model->fresh()),
        ], 200)->header('Cache-Control', 'private, no-cache, must-revalidate');
    }

    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $model = $this->news->findPublishedForShowBySlug($slug);

        if (! $model) {
            return response()->json(['success' => false, 'message' => 'Haber bulunamadı.'], 404);
        }

        try {
            NewsEngagement::recordView($model, $request);
        } catch (\Throwable) {
            // Okunma logu başarısız olsa da haber metni sunulsun.
        }

        return response()->json([
            'success' => true,
            'news' => $this->news->transformItem($model->fresh()),
        ], 200)->header('Cache-Control', 'private, no-cache, must-revalidate');
    }

    public function recordEngagement(Request $request, int $id): JsonResponse
    {
        $metric = strtolower(trim((string) $request->input('metric', '')));
        if (in_array($metric, NewsEngagement::allowedReactions(), true)) {
            $request->merge(['reaction' => $metric]);

            return $this->recordReaction($request, $id);
        }

        $allowed = [NewsEngagement::METRIC_SHARES, NewsEngagement::METRIC_PNG_DOWNLOADS];
        if (! in_array($metric, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz işlem türü.',
            ], 422);
        }

        $model = $this->news->findPublishedForShow($id);

        if (! $model) {
            return response()->json(['success' => false, 'message' => 'Haber bulunamadı.'], 404);
        }

        NewsEngagement::bump($model, $metric);

        $fresh = $model->fresh();

        return response()->json([
            'success' => true,
            'engagement' => NewsEngagement::metrics(is_array($fresh->stats) ? $fresh->stats : null),
        ]);
    }

    public function recordReaction(Request $request, int $id): JsonResponse
    {
        $reaction = $this->resolvePublicReactionKey($request);
        if ($reaction === '') {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz tepki türü. Lütfen tekrar deneyin.',
            ], 422);
        }

        if (! in_array($reaction, NewsEngagement::allowedReactions(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz tepki türü.',
            ], 422);
        }

        $model = $this->news->findPublishedForShow($id);
        if (! $model) {
            return response()->json(['success' => false, 'message' => 'Haber bulunamadı.'], 404);
        }

        $counts = NewsEngagement::recordReaderReaction($model, $reaction);

        return response()->json([
            'success' => true,
            'reactions' => $counts,
            'engagement' => NewsEngagement::metrics(is_array($model->fresh()->stats) ? $model->fresh()->stats : null),
        ], 200)->header('Cache-Control', 'private, no-cache, must-revalidate');
    }

    public function submit(Request $request): JsonResponse
    {
        $slotKind = (string) $request->input('gazette_slot_kind', '');
        $typeKeys = implode(',', array_keys(config('news.types', [])));

        $rules = [
            'title' => 'required|string|min:8|max:255',
            'location_badge' => 'nullable|string|max:150',
            'author' => 'nullable|string|max:120',
            'anonymous' => 'nullable|boolean',
            'rumuz' => 'nullable|string|max:80',
            'gazette_slot_kind' => 'nullable|string|in:lead,brief,corner',
            'gazette_slot_key' => 'nullable|string|max:64',
            'gazette_slot_label' => 'nullable|string|max:150',
            'gazette_issue_id' => 'nullable|integer|exists:gazette_issues,id',
            'image_preset' => 'nullable|string|max:255',
            'custom_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];

        if ($slotKind === 'lead') {
            $rules['description'] = 'required|string|min:20|max:1200';
            $rules['news_type'] = 'nullable|string|in:'.$typeKeys;
            $rules['gazette_issue_id'] = 'required|integer|exists:gazette_issues,id';
        } elseif ($slotKind === 'corner') {
            $rules['description'] = 'required|string|min:30|max:5000';
            $rules['news_type'] = 'required|string|in:'.$typeKeys;
            $rules['gazette_issue_id'] = 'required|integer|exists:gazette_issues,id';
            $rules['gazette_slot_key'] = 'required|string|max:64';
            $rules['rumuz'] = 'required|string|min:1|max:80';
        } else {
            $rules['description'] = 'required|string|min:30|max:5000';
            $rules['news_type'] = 'required|string|in:'.$typeKeys;
            $rules['gazette_issue_id'] = 'required|integer|exists:gazette_issues,id';
        }

        $validator = Validator::make($request->all(), $rules, [
            'gazette_issue_id.required' => 'Lütfen mevcut veya gelecek sayıyı seçin.',
            'news_type.required' => 'Lütfen haber türünü seçin.',
            'rumuz.required' => 'Lütfen köşe rumuzunu girin.',
            'custom_image.image' => 'Yüklenen dosya geçerli bir görsel değil.',
            'custom_image.max' => 'Görsel en fazla 5 MB olabilir.',
            'custom_image.mimes' => 'Görsel yalnızca JPEG, PNG veya WebP olabilir.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lütfen eksik veya hatalı alanları düzeltin.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $newsType = $data['news_type'] ?? 'guild_rumor';
        if ($slotKind === 'lead') {
            $newsType = $data['news_type'] ?? 'guild_news';
        }
        $typeConfig = config('news.types.'.$newsType, config('news.types.guild_rumor'));

        $requestedIssueId = isset($data['gazette_issue_id']) ? (int) $data['gazette_issue_id'] : null;
        $targetIssue = $this->gazetteIssues->currentEditableIssue();

        if ($requestedIssueId && (int) $requestedIssueId !== (int) $targetIssue->id) {
            $requestedIssue = GazetteIssue::query()->find($requestedIssueId);
            if (! $requestedIssue || ! $this->gazetteIssues->issueAcceptsSubmissions($requestedIssue)) {
                if ($this->gazetteIssues->issueIsLocked($requestedIssueId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bu gazete sayısı yayımlandı; yeni gönderi yalnızca açık sayıya yapılabilir.',
                    ], 422);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Gönderiler yalnızca güncel veya gelecek haftanın açık sayısına alınır.',
                ], 422);
            }

            $targetIssue = $requestedIssue;
        }

        $slotKind = $data['gazette_slot_kind'] ?? null;
        $rumuz = isset($data['rumuz']) ? trim((string) $data['rumuz']) : '';

        $anonymous = $slotKind === 'corner' ? false : $request->boolean('anonymous');
        if ($slotKind === 'corner') {
            $author = $rumuz !== '' ? $rumuz : 'Köşe Yazarı';
        } else {
            $author = $anonymous ? 'Anonim Gözcü' : (trim((string) ($data['author'] ?? '')) !== '' ? trim((string) $data['author']) : 'Sokak Muhabiri');
        }

        $submissionTags = [
            'lead' => 'Manşet Gönderisi',
            'corner' => 'Köşe Yazısı Gönderisi',
        ];
        $tag = $submissionTags[$slotKind] ?? 'Söylenti Gönderisi';

        $slotLabel = isset($data['gazette_slot_label']) ? trim((string) $data['gazette_slot_label']) : '';
        $authorRoles = [
            'lead' => $slotLabel !== '' ? "Manşet — {$slotLabel}" : 'Okuyucu — manşet',
            'corner' => $slotLabel !== ''
                ? "Köşe — {$slotLabel}".(! $anonymous && $rumuz !== '' ? " ({$rumuz})" : '')
                : 'Köşe yazarı'.(! $anonymous && $rumuz !== '' ? " ({$rumuz})" : ''),
        ];
        $authorRole = $authorRoles[$slotKind] ?? 'Halktan Gelen İhbar';

        $submission = array_filter([
            'gazette_slot_kind' => $slotKind,
            'gazette_slot_key' => $data['gazette_slot_key'] ?? null,
            'gazette_slot_label' => $slotLabel !== '' ? $slotLabel : null,
            'gazette_issue_id' => $targetIssue->id,
            'rumuz' => $slotKind === 'corner' && $rumuz !== '' ? $rumuz : (! $anonymous && $rumuz !== '' ? $rumuz : null),
        ], static fn ($value) => $value !== null && $value !== '');
        if ($anonymous && $slotKind !== 'corner') {
            $submission['anonymous'] = true;
        }
        $stats = [
            'submission' => $submission,
        ];

        $useAutoImage = ! $request->hasFile('custom_image')
            && (empty($data['image_preset']) || $data['image_preset'] === '__gazette_auto__');
        $imagePrefix = match ($slotKind) {
            'lead' => 'lead',
            'corner' => 'corner',
            default => 'rumor',
        };

        $imageUrl = 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg';
        if ($slotKind === 'corner') {
            $useAutoImage = false;
        } elseif ($slotKind !== 'lead') {
            try {
                GazetteSubmissionImageStore::ensureDirectoryExists();
                $imageUrl = GazetteSubmissionImageStore::resolveUploadedOrPreset(
                    $request->file('custom_image'),
                    $data['image_preset'] ?? null,
                    'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
                    $imagePrefix,
                );
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => $e instanceof \RuntimeException
                        ? $e->getMessage()
                        : 'Görsel yüklenemedi.',
                ], 500);
            }
        }

        $news = News::create([
            'title' => $data['title'],
            'category' => $typeConfig['label'],
            'news_type' => $newsType,
            'gazette_issue_id' => $targetIssue->id,
            'badge_color' => $typeConfig['badge_color'],
            'category_icon' => $typeConfig['category_icon'],
            'tag' => $tag,
            'tag_icon' => 'icon-base ti tabler-message-2-share',
            'date_text' => WorldCalendar::formatNewsStamp(now()) ?? now()->locale(app()->getLocale())->translatedFormat('d M Y, H:i'),
            'description' => $data['description'],
            'image_url' => $imageUrl,
            'location_badge' => $data['location_badge'] ?? null,
            'author' => $author,
            'author_role' => $authorRole,
            'stats' => $stats,
            'status' => 'pending',
            'is_active' => false,
            'order' => (int) News::max('order') + 1,
        ]);

        if ($useAutoImage) {
            GazetteNewsImage::assignFromContent($news);
            $news->saveQuietly();
        }

        $successMessage = match ($slotKind) {
            'lead' => 'Manşet editöre ulaştı. Onaylanınca yalnızca üst slaytta döner; haber listesine girmez.',
            'corner' => 'Köşe yazınız editöre ulaştı. Onaydan sonra seçtiğiniz köşede basılır.',
            default => 'Gönderiniz editöre ulaştı. Onaydan sonra gazetede yer alır.',
        };

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'submission_id' => $news->id,
        ], 201);
    }

    public function submitFlash(Request $request): JsonResponse
    {
        $typeKeys = implode(',', array_keys(config('news.types', [])));
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:8|max:255',
            'news_type' => 'required|string|in:'.$typeKeys,
            'description' => 'required|string|min:80|max:12000',
            'location_badge' => 'nullable|string|max:150',
            'author' => 'nullable|string|max:120',
            'anonymous' => 'nullable|boolean',
            'image_preset' => 'nullable|string|max:255',
            'custom_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'title.required' => 'Başlık zorunludur.',
            'title.min' => 'Başlık en az 8 karakter olmalıdır.',
            'news_type.required' => 'Lütfen haber türünü seçin.',
            'description.required' => 'Haber metni zorunludur.',
            'description.min' => 'Sıcak haber metni en az 80 karakter olmalıdır.',
            'custom_image.image' => 'Yüklenen dosya geçerli bir görsel değil.',
            'custom_image.max' => 'Görsel en fazla 5 MB olabilir.',
            'custom_image.mimes' => 'Görsel yalnızca JPEG, PNG veya WebP olabilir.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lütfen sıcak haber alanlarını kontrol edin.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $typeConfig = config('news.types.'.$data['news_type']);
        $anonymous = $request->boolean('anonymous');
        $author = $anonymous ? 'Anonim Gözcü' : (trim((string) ($data['author'] ?? '')) !== '' ? trim((string) $data['author']) : 'Sokak Muhabiri');

        $useAutoImage = ! $request->hasFile('custom_image')
            && (empty($data['image_preset']) || $data['image_preset'] === '__gazette_auto__');

        try {
            GazetteSubmissionImageStore::ensureDirectoryExists();
            $imageUrl = GazetteSubmissionImageStore::resolveUploadedOrPreset(
                $request->file('custom_image'),
                $data['image_preset'] ?? null,
                'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
                'flash',
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'Görsel yüklenemedi.',
            ], 500);
        }

        $submission = ['gazette_slot_kind' => 'flash'];
        if ($anonymous) {
            $submission['anonymous'] = true;
        }

        $news = News::create([
            'title' => $data['title'],
            'category' => $typeConfig['label'],
            'news_type' => $data['news_type'],
            'badge_color' => $typeConfig['badge_color'],
            'category_icon' => $typeConfig['category_icon'],
            'tag' => 'Flash Haber Gönderisi',
            'tag_icon' => 'icon-base ti tabler-urgent',
            'date_text' => WorldCalendar::formatNewsStamp(now()) ?? now()->locale(app()->getLocale())->translatedFormat('d M Y, H:i'),
            'description' => $data['description'],
            'image_url' => $imageUrl,
            'location_badge' => $data['location_badge'] ?? null,
            'author' => $author,
            'author_role' => 'Flash haber — editör onayı bekliyor',
            'stats' => ['submission' => $submission],
            'status' => 'pending',
            'is_active' => false,
            'order' => (int) News::max('order') + 1,
        ]);

        if ($useAutoImage) {
            GazetteNewsImage::assignFromContent($news);
            $news->saveQuietly();
        }

        return response()->json([
            'success' => true,
            'message' => 'Flash haber editöre ulaştı. Onaylanınca ek sayı (ör. 2.1) olarak basılır.',
            'submission_id' => $news->id,
        ], 201);
    }

    public function publishExtra(Request $request): JsonResponse
    {
        if (! GazetteEditorGate::canManage($request->user())) {
            return response()->json([
                'success' => false,
                'message' => __('Access denied. You do not have permission to manage news.'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:8|max:255',
            'news_type' => 'required|string|in:'.implode(',', array_keys(config('news.types', []))),
            'description' => 'required|string|min:80|max:12000',
            'location_badge' => 'nullable|string|max:150',
            'author' => 'nullable|string|max:120',
            'image_preset' => 'nullable|string|max:255',
            'custom_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lütfen sıcak haber alanlarını kontrol edin.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $imageUrl = null;

        try {
            GazetteSubmissionImageStore::ensureDirectoryExists();
            if ($request->hasFile('custom_image') || ! empty($data['image_preset'])) {
                $imageUrl = GazetteSubmissionImageStore::resolveUploadedOrPreset(
                    $request->file('custom_image'),
                    $data['image_preset'] ?? null,
                    'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
                    'hot',
                );
            }
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'Görsel yüklenemedi.',
            ], 500);
        }

        try {
            $result = $this->gazetteIssues->publishExtraIssue($data, $imageUrl);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sıcak haber sayısı basılamadı.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sıcak haber ek sayısı basıldı ve arşive eklendi.',
            'issue' => $result['issue'],
            'news_id' => $result['news']->id,
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

    protected function resolvePublicReactionKey(Request $request): string
    {
        $candidates = [
            $request->input('reaction'),
            $request->input('metric'),
            $request->input('type'),
            $request->json('reaction'),
            $request->json('metric'),
        ];

        foreach ($candidates as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $key = strtolower(trim((string) $value));
            if ($key !== '') {
                return $key;
            }
        }

        return '';
    }
}
