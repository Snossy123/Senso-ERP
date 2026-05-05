<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AssertsAdminOrPermission;
use App\Models\Role;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use AssertsAdminOrPermission;

    public function __construct(
        protected UserManagementService $userService
    ) {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $this->assertAdminOrPermission('roles.view');

            return $next($request);
        })->only(['index', 'edit', 'permissions']);

        $this->middleware(function ($request, $next) {
            $this->assertAdminOrPermission('roles.create');

            return $next($request);
        })->only(['create', 'store']);

        $this->middleware(function ($request, $next) {
            $this->assertAdminOrPermission('roles.edit');

            return $next($request);
        })->only(['update']);

        $this->middleware(function ($request, $next) {
            $this->assertAdminOrPermission('roles.delete');

            return $next($request);
        })->only(['destroy']);
    }

    public function index()
    {
        $result = $this->userService->getRoles();
        $roles = $result['roles'];

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissionsGrouped = $this->userService->getPermissionsGrouped();

        return view('admin.roles.create', compact('permissionsGrouped'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
        ]);

        $validated['permissions'] = $request->input('permissions', []);
        $role = $this->userService->createRole($validated);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => ['id' => $role->id],
        ]);
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissionsGrouped = $this->userService->getPermissionsGrouped();

        return view('admin.roles.edit', compact('role', 'permissionsGrouped'));
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
        ]);

        $validated['permissions'] = $request->input('permissions', []);
        $role = $this->userService->updateRole($role, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        try {
            $this->userService->deleteRole($role);

            return response()->json(['success' => true, 'message' => 'Role deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function permissions(Role $role): JsonResponse
    {
        $permissions = $role->permissions()->pluck('id');

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
        ]);
    }
}
