<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function index()
    {
        $tenants = Tenant::with('plan')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('tenants.index', compact('tenants'));
    }

    public function create()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('tenants.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'settings' => 'nullable|array',
            'plan_id' => 'nullable|exists:plans,id',
            'trial_days' => 'nullable|integer|min:0|max:60',
            'currency' => 'nullable|string|size:3',
            'language' => ['nullable', 'string', 'max:10', Rule::in(Locale::supportedCodes())],
            'timezone' => 'nullable|string|max:50',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = true;
        $validated['status'] = 'trial';

        $tenant = $this->tenantService->createTenant($validated);

        return redirect()->route('tenants.show', $tenant)
            ->with('success', 'Tenant created successfully. Trial period started.');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['plan', 'users', 'products', 'sales', 'orders', 'usageTrackings']);
        
        $usage = $this->tenantService->checkLimits($tenant);
        $daysUntilTrial = $this->tenantService->getDaysUntilTrialEnds($tenant);
        $daysUntilSubscription = $this->tenantService->getDaysUntilSubscriptionEnds($tenant);
        
        return view('tenants.show', compact('tenant', 'usage', 'daysUntilTrial', 'daysUntilSubscription'));
    }

    public function edit(Tenant $tenant)
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenant->id)],
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
            'trial_ends_at' => 'nullable|date',
            'subscription_ends_at' => 'nullable|date',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => ['nullable', Rule::in(['trial', 'active', 'expired', 'suspended'])],
            'currency' => 'nullable|string|size:3',
            'language' => ['nullable', 'string', 'max:10', Rule::in(Locale::supportedCodes())],
            'timezone' => 'nullable|string|max:50',
            'tax_settings' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        if (isset($validated['plan_id'])) {
            $plan = Plan::find($validated['plan_id']);
            if ($plan && $plan->id !== $tenant->plan_id) {
                $this->tenantService->assignPlan($tenant, $plan);
                unset($validated['plan_id']);
            }
        }
        
        $tenant->update($validated);

        return redirect()->route('tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->users()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete tenant with existing users.');
        }

        $tenant->delete();
        return redirect()->route('tenants.index')->with('success', 'Tenant deleted successfully.');
    }

    public function toggleStatus(Tenant $tenant)
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        return redirect()->back()->with('success', 'Tenant status updated.');
    }

    public function suspend(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->tenantService->suspendTenant($tenant, $validated['reason'] ?? null);
        
        return redirect()->route('tenants.show', $tenant)
            ->with('success', 'Tenant has been suspended.');
    }

    public function activate(Tenant $tenant)
    {
        $this->tenantService->activateTenant($tenant);
        
        return redirect()->route('tenants.show', $tenant)
            ->with('success', 'Tenant has been activated.');
    }

    public function upgradePlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $this->tenantService->assignPlan($tenant, $plan);
        
        return redirect()->route('tenants.show', $tenant)
            ->with('success', "Tenant has been upgraded to {$plan->name} plan.");
    }

    public function loginAs(Request $request, Tenant $tenant)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $user = $tenant->users()->first();
        
        if (!$user) {
            return redirect()->back()->with('error', 'No users found for this tenant.');
        }

        if ($request->user_id) {
            $user = $tenant->users()->find($request->user_id);
            if (!$user) {
                return redirect()->back()->with('error', 'User not found in this tenant.');
            }
        }

        session(['admin_logged_in_as_tenant' => $tenant->id]);
        session(['admin_logged_in_as_user' => $user->id]);
        
        auth()->login($user);

        return redirect('/dashboard')->with('success', "Logged in as {$tenant->name}");
    }

    public function syncUsage(Tenant $tenant)
    {
        $this->tenantService->syncUsage($tenant);
        
        return redirect()->route('tenants.show', $tenant)
            ->with('success', 'Usage statistics synchronized.');
    }

    public function updateSettings(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3',
            'language' => 'required|string|max:10',
            'timezone' => 'required|string|max:50',
            'tax_settings' => 'nullable|array',
        ]);

        $tenant->update($validated);

        return redirect()->route('tenants.show', $tenant)
            ->with('success', 'Settings updated successfully.');
    }
}
