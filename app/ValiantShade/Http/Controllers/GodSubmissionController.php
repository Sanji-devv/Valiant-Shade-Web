<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\GodDomainOverride;
use App\Models\GodSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GodSubmissionController extends Controller
{
    public function approve(Request $request, GodSubmission $submission): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($submission->isPending(), 404);

        $slug = $submission->corrects_slug ?: $submission->slug;
        if ($slug === null || $slug === '') {
            $slug = \Illuminate\Support\Str::slug($submission->name);
        }

        DB::transaction(function () use ($submission, $slug): void {
            $override = GodDomainOverride::query()->whereKey($slug)->lockForUpdate()->first();
            $oldImage = $override?->profile_image;

            GodDomainOverride::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $submission->name,
                    'epithet' => $submission->epithet,
                    'alignment' => $submission->alignment,
                    'category' => $submission->category,
                    'domains' => $submission->domains ?? [],
                    'subdomains' => $submission->subdomains ?? [],
                    'weapon' => $submission->weapon,
                    'summary' => $submission->summary,
                    'profile_text' => $submission->profile_text,
                    'profile_image' => $submission->profile_image ?? $override?->profile_image,
                ],
            );

            $submission->update([
                'approval_status' => GodSubmission::APPROVAL_APPROVED,
                'slug' => $slug,
            ]);

            if ($submission->profile_image && $oldImage && $oldImage !== $submission->profile_image) {
                Storage::disk('public')->delete($oldImage);
            }
        });

        return redirect()->route('valiant-shade.gods.index')
            ->with('success', __('God submission approved and published to the pantheon.'));
    }

    public function reject(Request $request, GodSubmission $submission): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $submission->update(['approval_status' => GodSubmission::APPROVAL_REJECTED]);

        return redirect()->route('valiant-shade.gods.index')
            ->with('success', __('God submission rejected.'));
    }
}