<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        $orphanIds = DB::table('branches')->whereNull('tenant_id')->pluck('id');
        if ($orphanIds->isNotEmpty()) {
            DB::table('users')->whereIn('branch_id', $orphanIds)->update(['branch_id' => null]);
            DB::table('branches')->whereNull('tenant_id')->delete();
        }

        try {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropUnique(['code']);
            });
        } catch (\Throwable) {
            foreach (Schema::getIndexes('branches') as $index) {
                if (($index['unique'] ?? false) && ($index['columns'] ?? []) === ['code']) {
                    Schema::table('branches', function (Blueprint $table) use ($index) {
                        $table->dropIndex($index['name']);
                    });
                    break;
                }
            }
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->unique(['tenant_id', 'code']);
        });

        if (class_exists(\App\Models\Tenant::class) && class_exists(\App\Services\BranchProvisioningService::class)) {
            $service = app(\App\Services\BranchProvisioningService::class);
            foreach (DB::table('tenants')->pluck('id') as $tenantId) {
                $tenant = \App\Models\Tenant::find($tenantId);
                if ($tenant) {
                    $service->ensureDefaultBranchesForTenant($tenant);
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'code']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
