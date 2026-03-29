<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\UsageTracking;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantApiController extends Controller
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function index(): JsonResponse
    {
        $tenants = Tenant::with('plan')->paginate(20);
        return response()->json($tenants);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['plan', 'usageTrackings']);
        
        return response()->json([
            'tenant' => $tenant,
            'usage' => $this->tenantService->checkLimits($tenant),
        ]);
    }

    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        return response()->json($plans);
    }

    public function usage(Tenant $tenant): JsonResponse
    {
        $usage = $this->tenantService->checkLimits($tenant);
        
        return response()->json([
            'tenant' => $tenant->only(['id', 'name', 'status']),
            'usage' => $usage,
        ]);
    }

    public function checkLimits(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'resource' => 'required|string|in:users,products,orders',
        ]);

        $tenant = Tenant::findOrFail($request->tenant_id);
        $withinLimits = $this->tenantService->isWithinLimits($tenant, $request->resource);
        
        return response()->json([
            'allowed' => $withinLimits,
            'resource' => $request->resource,
            'tenant' => $tenant->only(['id', 'name']),
        ]);
    }

    public function upgradePlan(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $this->tenantService->assignPlan($tenant, $plan);
        
        return response()->json([
            'message' => "Upgraded to {$plan->name}",
            'tenant' => $tenant->fresh(['plan']),
        ]);
    }

    public function suspend(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->tenantService->suspendTenant($tenant, $request->reason);
        
        return response()->json([
            'message' => 'Tenant suspended',
            'tenant' => $tenant->fresh(),
        ]);
    }

    public function activate(Tenant $tenant): JsonResponse
    {
        $this->tenantService->activateTenant($tenant);
        
        return response()->json([
            'message' => 'Tenant activated',
            'tenant' => $tenant->fresh(),
        ]);
    }

    public function syncUsage(Tenant $tenant): JsonResponse
    {
        $this->tenantService->syncUsage($tenant);
        
        return response()->json([
            'message' => 'Usage synchronized',
            'usage' => $this->tenantService->checkLimits($tenant),
        ]);
    }

    public function updateSettings(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'currency' => 'sometimes|string|size:3',
            'language' => 'sometimes|string|max:10',
            'timezone' => 'sometimes|string|max:50',
            'tax_settings' => 'sometimes|array',
        ]);

        $tenant->update($request->only(['currency', 'language', 'timezone', 'tax_settings']));
        
        return response()->json([
            'message' => 'Settings updated',
            'tenant' => $tenant->fresh(),
        ]);
    }
}
