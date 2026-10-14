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
        protected RoleService $roleService,
        protected \App\ValiantShade\Services\AuditLogService $auditLogService
    ) {}

    /**
     * Check if current user is an authorized Administrator.
     */
    protected function authorizeAdmin(Request $request): ?JsonResponse
    {
        $user = auth()->user();

        // Strictly must be authenticated and strictly an Administrator (No bypass)
        $hasAccess = $user && $user->isAdmin();

        if (!$hasAccess) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Access denied. Strictly restricted to system Administrators.'),
                ], 403);
            }
            abort(403, __('Access denied. Strictly restricted to system Administrators.'));
        }

        return null;
    }

    /**
     * Display the Roles & Permissions Management page.
     */
    public function index(Request $request): View|JsonResponse|Response
    {
        if ($deny = $this->authorizeAdmin($request)) {
            return $deny;
        }

        $roles = $this->roleService->getAllRoles();
        $permissions = $this->roleService->getPermissionCatalog();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'roles' => $roles,
                'permissions' => $permissions,
            ]);
        }

        return view('valiant-shade.content.apps.app-access-roles', compact(
            'roles',
            'permissions'
        ));
    }

    /**
     * Create a new role.
     */
    public function store(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeAdmin($request)) {
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

            // Audit Trail
            $this->auditLogService->log(
                'role.created',
                __('Created role ":name" (:slug) with priority #:priority.', [
                    'name' => $role['name'],
                    'slug' => $role['slug'],
                    'priority' => $role['priority'] ?? 10,
                ]),
                'Role',
                $role['id'],
                [],
                $role
            );

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
        if ($deny = $this->authorizeAdmin($request)) {
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
            $oldRole = $this->roleService->findRole($role);
            $updated = $this->roleService->updateRole($role, $validated);

            // Audit Trail
            $this->auditLogService->log(
                'role.updated',
                __('Updated role ":name" (:slug).', [
                    'name' => $updated['name'],
                    'slug' => $updated['slug'],
                ]),
                'Role',
                $updated['id'],
                $oldRole ?: [],
                $updated
            );

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
        if ($deny = $this->authorizeAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'permission' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        try {
            $oldRole = $this->roleService->findRole($role);
            $updated = $this->roleService->togglePermission($role, $validated['permission'], $validated['enabled']);

            // Audit Trail
            $actionDesc = $validated['enabled'] ? __('Granted') : __('Revoked');
            $this->auditLogService->log(
                'permission.toggled',
                __(':action permission ":perm" for role ":name".', [
                    'action' => $actionDesc,
                    'perm' => $validated['permission'],
                    'name' => $updated['name'],
                ]),
                'Role',
                $updated['id'],
                ['permission' => $validated['permission'], 'enabled' => !$validated['enabled']],
                ['permission' => $validated['permission'], 'enabled' => $validated['enabled']]
            );

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
        if ($deny = $this->authorizeAdmin($request)) {
            return $deny;
        }

        try {
            $oldRole = $this->roleService->findRole($role);
            $deleted = $this->roleService->deleteRole($role);
            if ($deleted) {
                // Audit Trail
                $this->auditLogService->log(
                    'role.deleted',
                    __('Deleted custom role ":name" (:slug).', [
                        'name' => $oldRole['name'] ?? (string) $role,
                        'slug' => $oldRole['slug'] ?? (string) $role,
                    ]),
                    'Role',
                    $oldRole['id'] ?? (string) $role,
                    $oldRole ?: [],
                    []
                );

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
