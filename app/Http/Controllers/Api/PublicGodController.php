<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GodSubmission;
use App\ValiantShade\Services\PublicGodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicGodController extends Controller
{
    public function __construct(
        private PublicGodService $gods,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'view' => 'nullable|string|in:grid,list',
            'page' => 'nullable|integer|min:1',
            'category' => 'nullable|string|in:major,minor',
            'alignment' => 'nullable|string|max:100',
            'domain' => 'nullable|string|max:255',
        ]);

        $payload = $this->gods->bootstrap(
            trim($validated['q'] ?? ''),
            $validated['view'] ?? 'list',
            (int) ($validated['page'] ?? 1),
            [
                'category' => $validated['category'] ?? '',
                'alignment' => $validated['alignment'] ?? '',
                'domain' => $validated['domain'] ?? '',
            ],
        );

        return response()->json(['success' => true, ...$payload]);
    }

    public function show(string $slug): JsonResponse
    {
        $god = $this->gods->findPublished($slug);
        if (! $god) {
            return response()->json(['success' => false, 'message' => __('God not found.')], 404);
        }

        return response()->json([
            'success' => true,
            'god' => $this->gods->transformDetail($god),
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $data = $this->validatedSubmission($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $submission = $this->createPending($request, $data);

        return response()->json([
            'success' => true,
            'message' => __('Your god record reached the archivists. It will appear after approval.'),
            'submission_id' => $submission->id,
        ], 201);
    }

    public function submitCorrection(Request $request, string $slug): JsonResponse
    {
        $original = $this->gods->findPublished($slug);
        if (! $original) {
            return response()->json(['success' => false, 'message' => __('God not found.')], 404);
        }

        if (GodSubmission::query()->pendingApproval()->where('corrects_slug', $slug)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('A correction for this god is already awaiting approval.'),
            ], 409);
        }

        $data = $this->validatedSubmission($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $submission = $this->createPending($request, $data, $slug);

        return response()->json([
            'success' => true,
            'message' => __('Your correction reached the archivists. It will apply after approval.'),
            'submission_id' => $submission->id,
        ], 201);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function validatedSubmission(Request $request): array|JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:120',
            'epithet' => 'nullable|string|max:255',
            'alignment' => 'nullable|string|max:100',
            'category' => 'required|string|in:major,minor',
            'domains' => 'nullable|string|max:5000',
            'subdomains' => 'nullable|string|max:5000',
            'weapon' => 'nullable|string|max:255',
            'summary' => 'nullable|string|max:10000',
            'profile_text' => 'nullable|string|max:30000',
            'submitter_notes' => 'nullable|string|max:10000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Please fix the highlighted fields.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['domains'] = $this->parseDomainLines($data['domains'] ?? null);
        $data['subdomains'] = $this->parseDomainLines($data['subdomains'] ?? null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createPending(Request $request, array $data, ?string $correctsSlug = null): GodSubmission
    {
        $slug = $correctsSlug ?? Str::slug($data['name']);

        $submission = new GodSubmission([
            'slug' => $slug,
            'corrects_slug' => $correctsSlug,
            'name' => $data['name'],
            'epithet' => $data['epithet'] ?? null,
            'alignment' => $data['alignment'] ?? null,
            'category' => $data['category'],
            'domains' => $data['domains'] ?? [],
            'subdomains' => $data['subdomains'] ?? [],
            'weapon' => $data['weapon'] ?? null,
            'summary' => $data['summary'] ?? null,
            'profile_text' => $data['profile_text'] ?? null,
            'submitter_notes' => $data['submitter_notes'] ?? null,
            'approval_status' => GodSubmission::APPROVAL_PENDING,
        ]);

        if ($request->user()) {
            $submission->user_id = $request->user()->id;
        }

        if ($request->hasFile('image')) {
            $submission->profile_image = $request->file('image')->store('gods_sub', 'public');
        }

        $submission->save();

        return $submission;
    }

    /**
     * @return list<string>
     */
    private function parseDomainLines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', $value ?? ''))
            ->map(static fn (string $line): string => trim($line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->values()
            ->all();
    }
}