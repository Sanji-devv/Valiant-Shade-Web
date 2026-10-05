<?php

namespace App\ValiantShade\Http\Controllers\apps;

use App\Models\User;
use App\Models\Role;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserList extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->hasPermission('users.manage') || auth()->user()?->hasPermission('roles.manage'), 403);

        $totalUsers = User::count();
        $adminUsers = User::where('role', 'ADMIN')->count();
        $bailiffUsers = User::where('role', 'BAILIFF')->count();
        $standardUsers = User::where('role', 'PLAYER')->count();
        $activeUsers = User::where('status', 2)->count();
        $pendingUsers = User::where('status', 1)->count();
        $isAdmin = auth()->user()->hasPermission('users.manage');
        $canManageRoles = auth()->user()->hasPermission('roles.manage');
        $roles = Role::orderBy('id')->get();

        return view('valiant-shade.content.apps.app-user-list', compact(
            'totalUsers',
            'adminUsers',
            'bailiffUsers',
            'standardUsers',
            'activeUsers',
            'pendingUsers',
            'isAdmin',
            'canManageRoles',
            'roles'
        ));
    }

    public function data(Request $request): JsonResponse
    {
        if (!auth()->user()?->hasPermission('users.manage')) {
            return response()->json(['data' => [], 'is_admin' => false, 'roles' => []]);
        }

        $users = User::orderBy('id', 'desc')->get();
        $isAdmin = auth()->user()->hasPermission('users.manage');

        $data = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'full_name' => $u->name,
                'role' => $u->roleDefinition?->name ?? $u->role,
                'role_slug' => $u->role,
                'username' => strtolower(explode('@', $u->email)[0]),
                'email' => $u->email,
                'current_character' => $u->current_character ?: '',
                'status' => (int) ($u->status ?: 2),
                'created_at' => $u->created_at ? $u->created_at->format('d.m.Y H:i') : '',
            ];
        });

        return response()->json([
            'data' => $data,
            'is_admin' => $isAdmin,
            'roles' => Role::orderBy('id')->get(['slug', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);

        $validated = $request->validate([
            'userFullname' => ['required', 'string', 'max:100'],
            'userEmail' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'userRole' => ['nullable', 'exists:roles,slug'],
            'currentCharacter' => ['nullable', 'string', 'max:100'],
        ]);

        $role = $validated['userRole'] ?? 'PLAYER';

        $user = User::create([
            'name' => $validated['userFullname'],
            'email' => $validated['userEmail'],
            'password' => Hash::make(Str::random(12)),
            'role' => $role,
            'status' => 2, // Active
            'current_character' => $validated['currentCharacter'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'user' => $user,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);

        $user = User::findOrFail($id);

        // Prevent self-deletion
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own logged-in account.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    public function destroyCharacter($id): JsonResponse
    {
        $user = is_numeric($id)
            ? User::find($id)
            : User::where('uuid', $id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $isAdmin = auth()->user()?->hasPermission('users.manage') ?? false;
        $isSelf = auth()->check() && auth()->id() === $user->id;

        if (!auth()->check() || (!$isAdmin && !$isSelf)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action: You do not have permission to delete this character.',
            ], 403);
        }

        $user->current_character = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Active character removed successfully.',
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'current_character' => null,
            ],
        ]);
    }

    public function syncCharacter(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        $user = auth()->user();
        $charName = $request->input('character');
        $user->current_character = !empty($charName) ? (string) $charName : null;
        $user->save();

        return response()->json([
            'success' => true,
            'current_character' => $user->current_character,
        ]);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);

        $validated = $request->validate([
            'role' => ['required', 'exists:roles,slug'],
        ]);

        $user = User::findOrFail($id);
        $user->role = $validated['role'];
        $user->save();

        return response()->json([
            'success' => true,
            'role' => $user->role,
            'role_name' => $user->roleDefinition?->name ?? $user->role,
        ]);
    }
}
