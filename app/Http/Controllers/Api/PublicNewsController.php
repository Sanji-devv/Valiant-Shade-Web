<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
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
            'rumuz' => 'nullable|string|max:80',
            'gazette_slot_kind' => 'nullable|string|in:lead,brief,corner',
            'gazette_slot_key' => 'nullable|string|max:64',
            'gazette_slot_label' => 'nullable|string|max:150',
            'gazette_issue_id' => 'nullable|integer|exists:gazette_issues,id',
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

        $requestedIssueId = isset($data['gazette_issue_id']) ? (int) $data['gazette_issue_id'] : null;
        $targetIssue = $this->gazetteIssues->currentEditableIssue();

        if ($requestedIssueId && (int) $requestedIssueId !== (int) $targetIssue->id) {
            if ($this->gazetteIssues->issueIsLocked($requestedIssueId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu gazete sayısı yayımlandı; yeni gönderi yalnızca açık sayıya yapılabilir.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gönderiler yalnızca düzenlenmekte olan güncel sayıya alınır.',
            ], 422);
        }

        $slotKind = $data['gazette_slot_kind'] ?? null;
        $rumuz = isset($data['rumuz']) ? trim((string) $data['rumuz']) : '';

        $anonymous = ! empty($data['anonymous']);
        $author = $anonymous ? 'Anonim Gözcü' : ($data['author'] ?? 'Sokak Muhabiri');
        if ($slotKind === 'corner' && ! $anonymous && $rumuz !== '') {
            $author = trim((string) ($data['author'] ?? '')) !== '' ? trim((string) $data['author']) : $rumuz;
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
            'rumuz' => ! $anonymous && $rumuz !== '' ? $rumuz : null,
        ], static fn ($value) => $value !== null && $value !== '');
        if ($anonymous) {
            $submission['anonymous'] = true;
        }
        $stats = [
            'submission' => $submission,
        ];

        $news = News::create([
            'title' => $data['title'],
            'category' => $typeConfig['label'],
            'news_type' => $data['news_type'],
            'gazette_issue_id' => $targetIssue->id,
            'badge_color' => $typeConfig['badge_color'],
            'category_icon' => $typeConfig['category_icon'],
            'tag' => $tag,
            'tag_icon' => 'icon-base ti tabler-message-2-share',
            'date_text' => WorldCalendar::formatNewsStamp(now()) ?? now()->locale(app()->getLocale())->translatedFormat('d M Y, H:i'),
            'description' => $data['description'],
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'location_badge' => $data['location_badge'] ?? null,
            'author' => $author,
            'author_role' => $authorRole,
            'stats' => $stats,
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
