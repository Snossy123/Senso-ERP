<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class RoleProvisioningService
{
    /**
     * Clone platform template roles (tenant_id null) into roles for the given tenant.
     * Returns map of slug => new role id.
     *
     * @return array<string, int>
     */
    public function cloneDefaultRolesForTenant(Tenant $tenant): array
    {
        $templates = Role::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->with('permissions')
            ->orderBy('id')
            ->get();

        $slugToId = [];

        foreach ($templates as $template) {
            $existing = Role::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('slug', $template->slug)
                ->first();

            if ($existing) {
                $slugToId[$template->slug] = $existing->id;

                continue;
            }

            $role = DB::transaction(function () use ($template, $tenant) {
                $r = Role::withoutGlobalScopes()->create([
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'description' => $template->description,
                    'guard_name' => $template->guard_name,
                    'is_active' => $template->is_active,
                    'tenant_id' => $tenant->id,
                ]);

                $permIds = $template->permissions->pluck('id')->all();
                $r->permissions()->sync($permIds);

                return $r;
            });

            $slugToId[$template->slug] = $role->id;
        }

        return $slugToId;
    }
}
