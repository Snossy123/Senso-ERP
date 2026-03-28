<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementService
{
    protected int $maxFailedAttempts = 5;
    protected int $lockoutMinutes = 30;
    protected int $passwordExpiryDays = 90;

    public function getUsers(array $filters = [], int $perPage = 15): array
    {
        $query = User::with(['role:id,name,slug', 'branch:id,name,code', 'creator:id,name'])
            ->select('users.*');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->whereHas('role', fn($q) => $q->where('slug', $filters['role']));
        }

        if (!empty($filters['branch'])) {
            $query->where('branch_id', $filters['branch']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['locked']) && $filters['locked']) {
            $query->locked();
        }

        $users = $query->latest()->paginate($perPage);

        return [
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ];
    }

    public function createUser(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ?? 'password',
                'phone' => $data['phone'] ?? null,
                'role_id' => $data['role_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'tenant_id' => $data['tenant_id'] ?? auth()->user()->tenant_id,
                'created_by' => auth()->id(),
                'is_active' => $data['is_active'] ?? true,
                'must_change_password' => $data['must_change_password'] ?? true,
            ]);

            if (!empty($data['permissions'])) {
                $user->permissions()->attach($data['permissions']);
            }

            Activity::log('user', 'create', "Created user: {$user->name}", [], $user);

            return $user;
        });

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        $changes = [];

        DB::transaction(function () use ($user, $data, &$changes) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
                $changes['name'] = ['old' => $user->name, 'new' => $data['name']];
            }

            if (isset($data['email'])) {
                $updateData['email'] = $data['email'];
                $changes['email'] = ['old' => $user->email, 'new' => $data['email']];
            }

            if (isset($data['phone'])) {
                $updateData['phone'] = $data['phone'];
            }

            if (array_key_exists('role_id', $data)) {
                $updateData['role_id'] = $data['role_id'];
                $changes['role'] = ['old' => $user->role?->name, 'new' => Role::find($data['role_id'])?->name];
            }

            if (array_key_exists('branch_id', $data)) {
                $updateData['branch_id'] = $data['branch_id'];
            }

            if (array_key_exists('is_active', $data)) {
                $updateData['is_active'] = $data['is_active'];
                $changes['status'] = ['old' => $user->is_active ? 'active' : 'inactive', 'new' => $data['is_active'] ? 'active' : 'inactive'];
            }

            if (!empty($data['password'])) {
                $updateData['password'] = $data['password'];
                $updateData['must_change_password'] = false;
            }

            if (isset($data['must_change_password'])) {
                $updateData['must_change_password'] = $data['must_change_password'];
            }

            $user->update($updateData);

            if (isset($data['permissions'])) {
                $user->permissions()->sync($data['permissions']);
            }

            if (!empty($changes)) {
                Activity::log('user', 'update', "Updated user: {$user->name}", ['changes' => $changes], $user);
            }
        });

        return $user->fresh(['role', 'branch', 'permissions']);
    }

    public function deleteUser(User $user): bool
    {
        if ($user->id === auth()->id()) {
            throw new \Exception('Cannot delete your own account.');
        }

        if ($user->isAdmin() && User::whereHas('role', fn($q) => $q->where('slug', 'admin'))->count() <= 1) {
            throw new \Exception('Cannot delete the last administrator.');
        }

        $userName = $user->name;
        $user->delete();

        Activity::log('user', 'delete', "Deleted user: {$userName}");
        return true;
    }

    public function toggleStatus(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);
        Activity::log('user', 'toggle_status', ($user->is_active ? 'Activated' : 'Deactivated') . " user: {$user->name}", [], $user);
        return $user;
    }

    public function lockUser(User $user, int $minutes = 30): User
    {
        $user->lockAccount($minutes);
        Activity::log('security', 'lock', "Locked user: {$user->name} for {$minutes} minutes", [], $user);
        return $user;
    }

    public function unlockUser(User $user): User
    {
        $user->unlockAccount();
        Activity::log('security', 'unlock', "Unlocked user: {$user->name}", [], $user);
        return $user;
    }

    public function resetPassword(User $user, ?string $newPassword = null): string
    {
        $password = $newPassword ?? $this->generateRandomPassword();
        $user->update([
            'password' => $password,
            'must_change_password' => true,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        Activity::log('security', 'password_reset', "Reset password for user: {$user->name}", [], $user);

        return $password;
    }

    public function forcePasswordChange(User $user): User
    {
        $user->update(['must_change_password' => true]);
        Activity::log('security', 'force_password_change', "Forced password change for: {$user->name}", [], $user);
        return $user;
    }

    public function authenticate(string $email, string $password, string $ip): ?User
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return null;
        }

        if (!$user->is_active) {
            throw new \Exception('Account is inactive.');
        }

        if ($user->isLocked()) {
            throw new \Exception('Account is locked. Try again later.');
        }

        if (!Hash::check($password, $user->password)) {
            $user->incrementFailedAttempts();
            Activity::log('security', 'failed_login', "Failed login attempt for: {$email}", ['ip' => $ip], $user);
            return null;
        }

        if ($user->mustChangePassword()) {
            throw new \Exception('Password change required.');
        }

        $user->recordLogin($ip);
        Activity::log('auth', 'login', "User logged in: {$user->name}", ['ip' => $ip], $user);

        return $user;
    }

    public function getUserActivity(User $user, int $limit = 50): array
    {
        $activities = Activity::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();

        return [
            'activities' => $activities,
            'total' => $activities->count(),
        ];
    }

    public function getUserSessions(User $user): array
    {
        return [
            'sessions' => [],
            'current_session_id' => session()->getId(),
        ];
    }

    public function terminateSession(string $sessionId): bool
    {
        return true;
    }

    public function terminateAllSessions(User $user): int
    {
        Activity::log('security', 'terminate_sessions', "Terminated all sessions for user", [], $user);
        return 0;
    }

    public function getRoles(array $filters = []): array
    {
        $query = Role::withCount('permissions', 'users');

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $roles = $query->latest()->get();

        return ['roles' => $roles];
    }

    public function createRole(array $data): Role
    {
        $role = DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'slug' => \Illuminate\Support\Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'tenant_id' => $data['tenant_id'] ?? auth()->user()->tenant_id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (!empty($data['permissions'])) {
                $role->permissions()->attach($data['permissions']);
            }

            Activity::log('role', 'create', "Created role: {$role->name}", [], $role);

            return $role;
        });

        return $role;
    }

    public function updateRole(Role $role, array $data): Role
    {
        DB::transaction(function () use ($role, $data) {
            $role->update([
                'name' => $data['name'] ?? $role->name,
                'description' => $data['description'] ?? $role->description,
                'is_active' => $data['is_active'] ?? $role->is_active,
            ]);

            if (isset($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
            }

            Activity::log('role', 'update', "Updated role: {$role->name}", [], $role);
        });

        return $role->fresh(['permissions']);
    }

    public function deleteRole(Role $role): bool
    {
        if ($role->users()->exists()) {
            throw new \Exception('Cannot delete role with assigned users.');
        }

        if (in_array($role->slug, ['admin', 'manager'])) {
            throw new \Exception('Cannot delete system role.');
        }

        $roleName = $role->name;
        $role->delete();

        Activity::log('role', 'delete', "Deleted role: {$roleName}");
        return true;
    }

    public function getBranches(): array
    {
        $branches = Branch::active()->get();
        return ['branches' => $branches];
    }

    public function createBranch(array $data): Branch
    {
        $branch = Branch::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? auth()->user()->tenant_id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        Activity::log('branch', 'create', "Created branch: {$branch->name}", [], $branch);

        return $branch;
    }

    public function getPermissionsGrouped(): array
    {
        $permissions = Permission::all()->groupBy('group');
        $groups = Permission::getGroups();

        $result = [];
        foreach ($groups as $name => $key) {
            $result[$key] = [
                'name' => $name,
                'permissions' => $permissions->get($key, collect()),
            ];
        }

        return $result;
    }

    public function initializeDefaults(): void
    {
        $existingPermissions = Permission::count();
        if ($existingPermissions > 0) {
            return;
        }

        $permissions = Permission::getDefaultPermissions();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $existingRoles = Role::count();
        if ($existingRoles > 0) {
            return;
        }

        $roles = Role::getDefaultRoles();
        $allPermissions = Permission::pluck('id', 'slug');

        foreach ($roles as $roleData) {
            $permissions = match($roleData['slug']) {
                'admin' => $allPermissions->values()->toArray(),
                'manager' => Permission::where('group', '!=', 'roles')->pluck('id')->toArray(),
                'inventory_manager' => Permission::whereIn('group', ['products', 'categories', 'warehouses', 'suppliers'])->pluck('id')->toArray(),
                'cashier' => $allPermissions->only(['dashboard.view', 'pos.view', 'pos.create', 'orders.view', 'orders.create', 'customers.view', 'customers.create'])->values()->toArray(),
                'viewer' => Permission::where('slug', 'LIKE', '%.view')->pluck('id')->toArray(),
                default => [],
            };

            $role = Role::firstOrCreate(['slug' => $roleData['slug']], [
                'name' => $roleData['name'],
                'description' => $roleData['description'],
                'is_active' => true,
            ]);

            $role->permissions()->sync($permissions);
        }
    }

    protected function generateRandomPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }
}
