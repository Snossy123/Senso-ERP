<?php

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RoleProvisioningService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('tenants')) {
            return;
        }

        $service = app(RoleProvisioningService::class);

        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use ($service) {
            $map = $service->cloneDefaultRolesForTenant($tenant);

            User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('role_id')
                ->cursor()
                ->each(function (User $user) use ($map) {
                    $role = Role::withoutGlobalScopes()->find($user->role_id);
                    if ($role && $role->tenant_id === null && isset($map[$role->slug])) {
                        DB::table('users')->where('id', $user->id)->update(['role_id' => $map[$role->slug]]);
                    }
                });
        });
    }

    public function down(): void
    {
        // Data restore is not supported; re-seed if needed.
    }
};
