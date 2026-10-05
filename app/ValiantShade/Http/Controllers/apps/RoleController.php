<?php

namespace App\ValiantShade\Http\Controllers\apps;

use App\Models\Role;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorizeRoleManagement();

        return response()->json([
            'roles' => Role::withCount('users')->orderBy('id')->get(),
            'permissions' => Role::permissionCatalog(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRoleManagement();
        $validated = $this->validateRole($request);
        $slug = strtoupper(Str::slug($validated['name'], '_'));
        $slug = $slug !== '' ? $slug : 'ROLE_' . Str::upper(Str::random(8));

        if (Role::where('slug', $slug)->exists()) {
            return response()->json(['message' => 'A role with this name already exists.'], 422);
        }

        $role = Role::create([
            'slug' => $slug,
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return response()->json(['success' => true, 'role' => $role], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorizeRoleManagement();
        $validated = $this->validateRole($request, $role);

        if ($role->slug === 'ADMIN' && !in_array('roles.manage', $validated['permissions'] ?? [], true)) {
            return response()->json(['message' => 'The Admin role must retain role-management permission.'], 422);
        }

        $role->update([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return response()->json(['success' => true, 'role' => $role->fresh()]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorizeRoleManagement();

        if ($role->is_system) {
            return response()->json(['message' => 'Built-in roles cannot be deleted.'], 422);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => 'Move users to another role before deleting this role.'], 422);
        }

        $role->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeRoleManagement(): void
    {
        abort_unless(auth()->user()?->hasPermission('roles.manage'), 403);
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(Role::permissionCatalog()))],
        ]);
    }
}