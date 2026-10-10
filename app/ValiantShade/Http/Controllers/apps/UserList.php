<?php

namespace App\ValiantShade\Http\Controllers\apps;

use App\Models\User;
use App\Models\Role;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserList extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();
        $isAdmin = $currentUser ? ($currentUser->isAdmin() || $currentUser->hasPermission('users.manage')) : true;
        $canManageRoles = Schema::hasTable('roles') && ($currentUser ? ($currentUser->isAdmin() || $currentUser->hasPermission('roles.manage')) : false);

        $totalUsers = User::count();
        $adminUsers = User::where('role', 'ADMIN')->count();
        $bailiffUsers = User::where('role', 'BAILIFF')->count();
        $standardUsers = User::where('role', 'PLAYER')->count();
        $activeUsers = User::where('status', 1)->count();
        $pendingUsers = User::where('status', 2)->count();

        $roles = Schema::hasTable('roles')
            ? Role::orderBy('id')->get()
            : collect([
                (object)['slug' => 'ADMIN', 'name' => 'Administrator'],
                (object)['slug' => 'GM', 'name' => 'Game Master'],
                (object)['slug' => 'BAILIFF', 'name' => 'Bailiff'],
                (object)['slug' => 'PLAYER', 'name' => 'Player'],
            ]);

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
        $users = User::with('roles')->orderBy('id', 'desc')->get();
        $currentUser = auth()->user();
        $isAdmin = $currentUser ? ($currentUser->isAdmin() || $currentUser->hasPermission('users.manage')) : true;

        $data = $users->map(function ($u) {
            $orderedRoles = $u->ordered_roles;
            $highest = $u->highest_role;

            return [
                'id' => $u->id,
                'full_name' => $u->name ?: ($u->real_name ?: $u->username),
                'role' => $highest?->name ?? 'Player',
                'role_slug' => $highest?->slug ?? 'PLAYER',
                'roles' => $orderedRoles->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                    'color' => $r->color ?? '#7367f0',
                    'icon' => $r->icon ?? 'ti tabler-shield',
                    'priority' => $r->priority ?? 10,
                ])->values()->all(),
                'username' => $u->username ?: strtolower(explode('@', $u->email)[0]),
                'email' => $u->email,
                'current_character' => $u->current_character ?: '',
                'status' => (int) ($u->status ?? 1),
                'created_at' => $u->created_at ? $u->created_at->format('d.m.Y H:i') : '',
            ];
        });

        $roles = Schema::hasTable('roles')
            ? Role::orderByDesc('priority')->orderBy('id')->get(['id', 'slug', 'name', 'color', 'icon', 'priority'])->toArray()
            : [
                ['slug' => 'ADMIN', 'name' => 'Administrator', 'color' => '#ea5455', 'priority' => 100],
                ['slug' => 'GM', 'name' => 'Game Master', 'color' => '#7367f0', 'priority' => 80],
                ['slug' => 'BAILIFF', 'name' => 'Bailiff', 'color' => '#ff9f43', 'priority' => 60],
                ['slug' => 'VETERAN', 'name' => 'Veteran Adventurer', 'color' => '#00cfe8', 'priority' => 40],
                ['slug' => 'MEMBER', 'name' => 'Guild Member', 'color' => '#28c76f', 'priority' => 20],
                ['slug' => 'PLAYER', 'name' => 'Player', 'color' => '#82868b', 'priority' => 10],
            ];

        return response()->json([
            'data' => $data,
            'is_admin' => $isAdmin,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (auth()->check()) {
            abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('users.manage'), 403);
        }

        $validated = $request->validate([
            'userFullname' => ['required', 'string', 'max:100'],
            'userEmail' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'userRole' => ['nullable', 'string', 'in:ADMIN,GM,BAILIFF,PLAYER'],
            'currentCharacter' => ['nullable', 'string', 'max:100'],
        ]);

        $role = $validated['userRole'] ?? 'PLAYER';

        $user = User::create([
            'display_name' => $validated['userFullname'],
            'name' => $validated['userFullname'],
            'real_name' => $validated['userFullname'],
            'email' => $validated['userEmail'],
            'password' => Hash::make(Str::random(12)),
            'role' => $role,
            'status' => 1, // Active in Valiant Shade
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
        if (auth()->check()) {
            abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('users.manage'), 403);

            if (auth()->id() === (int) $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own logged-in account.',
                ], 422);
            }
        }

        $user = User::findOrFail($id);
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

        $isAdmin = auth()->check() ? (auth()->user()->isAdmin() || auth()->user()->hasPermission('users.manage')) : true;
        $isSelf = auth()->check() && auth()->id() === $user->id;

        if (auth()->check() && !$isAdmin && !$isSelf) {
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
        $user = auth()->user() ?: User::orderBy('id', 'asc')->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No user found.',
            ], 404);
        }

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
        if (auth()->check()) {
            abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('users.manage'), 403);
        }

        $validated = $request->validate([
            'role' => ['nullable', 'string'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        $user = User::findOrFail($id);

        if (!empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        } elseif (!empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        $highest = $user->highest_role;

        return response()->json([
            'success' => true,
            'role' => $highest?->slug ?? 'PLAYER',
            'role_name' => $highest?->name ?? 'Player',
            'roles' => $user->ordered_roles,
        ]);
    }
}
