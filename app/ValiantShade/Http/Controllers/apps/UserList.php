<?php

namespace App\ValiantShade\Http\Controllers\apps;

use App\Models\User;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserList extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $adminUsers = User::where('role', 'Admin')->count();
        $bailiffUsers = User::where('role', 'Bailiff')->count();
        $standardUsers = User::where('role', 'User')->count();
        $activeUsers = User::where('status', 2)->count();
        $pendingUsers = User::where('status', 1)->count();
        $isAdmin = auth()->check() && auth()->user()->role === 'Admin';

        return view('valiant-shade.content.apps.app-user-list', compact(
            'totalUsers',
            'adminUsers',
            'bailiffUsers',
            'standardUsers',
            'activeUsers',
            'pendingUsers',
            'isAdmin'
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $users = User::orderBy('id', 'desc')->get();
        $isAdmin = auth()->check() && auth()->user()->role === 'Admin';

        $data = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'full_name' => $u->name,
                'role' => $u->role ?: 'User',
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
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userFullname' => ['required', 'string', 'max:100'],
            'userEmail' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'userRole' => ['nullable', 'in:Admin,Bailiff,User'],
            'currentCharacter' => ['nullable', 'string', 'max:100'],
        ]);

        $role = $validated['userRole'] ?? 'User';
        if (!in_array($role, ['Admin', 'Bailiff', 'User'])) {
            $role = 'User';
        }

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
        // Only users with Admin role can delete
        if (!auth()->check() || auth()->user()->role !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action: Only Administrators can delete users.',
            ], 403);
        }

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

        $isAdmin = auth()->check() && auth()->user()->role === 'Admin';
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
}
