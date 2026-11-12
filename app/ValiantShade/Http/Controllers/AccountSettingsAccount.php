<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\CharacterProfile;
use App\Models\User;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountSettingsAccount extends Controller
{
    public function index()
    {
        $user = Auth::user() ?: User::orderBy('id', 'asc')->first();
        $displayName = '';
        if ($user) {
            $displayName = $user->getRawOriginal('display_name')
                ?? $user->getRawOriginal('name')
                ?? '';
            $displayName = (string) $displayName;
        }

        $characters = $user ? CharacterProfile::where('user_id', $user->id)->get() : collect();
        if ($characters->isEmpty()) {
            $characters = CharacterProfile::orderBy('character_name')->get();
        }

        return view('valiant-shade.content.pages.pages-account-settings-account', compact(
            'user',
            'displayName',
            'characters'
        ));
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user() ?: User::orderBy('id', 'asc')->first();

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('User not found.')], 404);
            }
            return redirect()->back()->with('error', __('User not found.'));
        }

        $validated = $request->validate([
            'displayName' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_character' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'in:en,tr'],
        ], [
            'username.required' => __('Username is required.'),
            'username.unique' => __('This username is already taken.'),
            'email.required' => __('Email address is required.'),
            'email.email' => __('Please enter a valid email address.'),
            'email.unique' => __('This email address is already taken.'),
        ]);

        $displayName = trim($validated['displayName'] ?? '');

        $user->display_name = $displayName;
        $user->name = $displayName;
        $user->username = $validated['username'];
        $user->email = $validated['email'];

        if (array_key_exists('current_character', $validated)) {
            $user->current_character = $validated['current_character'] ?: null;
        }

        $user->save();

        if (!empty($validated['language'])) {
            session(['locale' => $validated['language']]);
            app()->setLocale($validated['language']);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Account settings successfully updated.'),
                'user' => [
                    'id' => $user->id,
                    'display_name' => $user->display_name,
                    'name' => $user->display_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'current_character' => $user->current_character,
                ],
            ]);
        }

        return redirect()->back()->with('success', __('Account settings successfully updated.'));
    }

    public function deactivate(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user() ?: User::orderBy('id', 'asc')->first();

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('User not found.')], 404);
            }
            return redirect()->back()->with('error', __('User not found.'));
        }

        $user->status = 0;
        $user->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Your account has been deactivated.'),
                'redirect' => route('valiant-shade.auth-login-basic'),
            ]);
        }

        if (Auth::check()) {
            Auth::logout();
        }

        return redirect()->route('valiant-shade.auth-login-basic')
            ->with('success', __('Your account has been deactivated.'));
    }
}
