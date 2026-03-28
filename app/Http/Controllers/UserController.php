<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserManagementService $userService
    ) {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission('users.view')) {
                abort(403, 'Access denied.');
            }
            return $next($request);
        })->only('index', 'show');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role', 'branch', 'is_active', 'locked']);
        $result = $this->userService->getUsers($filters);
        
        $users = $result['users'];
        $roles = \App\Models\Role::active()->get(['id', 'name', 'slug']);
        $branches = $this->userService->getBranches()['branches'];

        return view('admin.users.index', compact('users', 'roles', 'branches'));
    }

    public function show(User $user)
    {
        $user->load(['role', 'branch', 'creator', 'permissions']);
        $activity = $this->userService->getUserActivity($user);
        
        return view('admin.users.show', compact('user', 'activity'));
    }

    public function create()
    {
        $roles = \App\Models\Role::active()->get(['id', 'name', 'slug']);
        $branches = $this->userService->getBranches()['branches'];
        $permissionsGrouped = $this->userService->getPermissionsGrouped();

        return view('admin.users.create', compact('roles', 'branches', 'permissionsGrouped'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'role_id' => 'nullable|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ]);

        $validated['permissions'] = $request->input('permissions', []);
        
        $user = $this->userService->createUser($validated);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => ['id' => $user->id]
        ]);
    }

    public function edit(User $user)
    {
        $user->load(['role', 'branch', 'permissions']);
        $roles = \App\Models\Role::active()->get(['id', 'name', 'slug']);
        $branches = $this->userService->getBranches()['branches'];
        $permissionsGrouped = $this->userService->getPermissionsGrouped();

        return view('admin.users.edit', compact('user', 'roles', 'branches', 'permissionsGrouped'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'role_id' => 'nullable|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ]);

        $validated['permissions'] = $request->input('permissions', []);
        
        $user = $this->userService->updateUser($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);
            return response()->json(['success' => true, 'message' => 'User deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function toggleStatus(User $user): JsonResponse
    {
        $this->userService->toggleStatus($user);
        return response()->json([
            'success' => true,
            'message' => 'Status updated.',
            'is_active' => $user->fresh()->is_active
        ]);
    }

    public function lock(User $user): JsonResponse
    {
        $this->userService->lockUser($user);
        return response()->json(['success' => true, 'message' => 'User locked.']);
    }

    public function unlock(User $user): JsonResponse
    {
        $this->userService->unlockUser($user);
        return response()->json(['success' => true, 'message' => 'User unlocked.']);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $password = $this->userService->resetPassword($user, $request->input('password'));
        
        return response()->json([
            'success' => true,
            'message' => 'Password reset.',
            'password' => $password
        ]);
    }

    public function forceChangePassword(User $user): JsonResponse
    {
        $this->userService->forcePasswordChange($user);
        return response()->json(['success' => true, 'message' => 'User must change password on next login.']);
    }
}
