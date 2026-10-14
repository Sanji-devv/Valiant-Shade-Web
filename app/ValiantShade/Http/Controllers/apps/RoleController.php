<?php

namespace App\ValiantShade\Http\Controllers\apps;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * Check if current user is an authorized Administrator.
     */
    protected function authorizeRoleManagement(Request $request, string $ability = 'read'): ?JsonResponse
    {
        $user = auth()->user();
        $permission = $ability === 'read' ? 'roles.read' : 'roles.update';
        $hasAccess = $user && (
            $user->isAdmin()
            || $user->hasPermission('roles.all')
            || $user->hasPermission($permission)
        );

        if (! $hasAccess) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Access denied. You do not have permission to manage roles.'),
                ], 403);
            }
            abort(403, __('Access denied. You do not have permission to manage roles.'));
        }

        return null;
    }

    /**
     * Display the Roles & Permissions Management page.
     */
    public function index(Request $request): View|JsonResponse|Response
    {
        if ($deny = $this->authorizeRoleManagement($request, 'read')) {
            return $deny;
        }

        $roles = $this->roleService->getAllRoles();
        $permissions = $this->roleService->getPermissionCatalog();
        $flatPermissions = $this->roleService->getFlatPermissionCatalog();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'roles' => $roles,
                'permissions' => $permissions,
                'flat_permissions' => $flatPermissions,
            ]);
        }

        return view('valiant-shade.content.apps.app-access-roles', compact(
            'roles',
            'permissions',
            'flatPermissions'
        ));
    }

    /**
     * Create a new role.
     */
    public function store(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeRoleManagement($request, 'write')) {
            return $deny;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:30',
            'icon' => 'nullable|string|max:50',
            'priority' => 'nullable|integer|min:0|max:100',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        try {
            $role = $this->roleService->createRole($validated);

            return response()->json([
                'success' => true,
                'message' => __('Role ":name" created successfully!', ['name' => $role['name']]),
                'role' => $role,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update an existing role.
     */
    public function update(Request $request, string|int $role): JsonResponse
    {
        if ($deny = $this->authorizeRoleManagement($request, 'write')) {
            return $deny;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:30',
            'icon' => 'nullable|string|max:50',
            'priority' => 'nullable|integer|min:0|max:100',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        try {
            $updated = $this->roleService->updateRole($role, $validated);

            return response()->json([
                'success' => true,
                'message' => __('Role ":name" updated successfully!', ['name' => $updated['name']]),
                'role' => $updated,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Quick toggle permission for a role.
     */
    public function togglePermission(Request $request, string|int $role): JsonResponse
    {
        if ($deny = $this->authorizeRoleManagement($request, 'write')) {
            return $deny;
        }

        $validated = $request->validate([
            'permission' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        try {
            $updated = $this->roleService->togglePermission($role, $validated['permission'], $validated['enabled']);

            return response()->json([
                'success' => true,
                'message' => __('Permission updated for role ":name".', ['name' => $updated['name']]),
                'role' => $updated,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a custom role.
     */
    public function destroy(Request $request, string|int $role): JsonResponse
    {
        if ($deny = $this->authorizeRoleManagement($request, 'write')) {
            return $deny;
        }

        try {
            $oldRole = $this->roleService->findRole($role);
            $deleted = $this->roleService->deleteRole($role);
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => __('Role was deleted successfully.'),
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => __('Role not found.'),
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
