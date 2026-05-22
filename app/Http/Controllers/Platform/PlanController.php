<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlanRequest;
use App\Http\Requests\Platform\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\PlatformModule;
use App\Services\Platform\PlanService;
use App\Services\Platform\SubscriptionStatsService;

class PlanController extends Controller
{
    public function __construct(
        protected PlanService $planService,
        protected SubscriptionStatsService $statsService
    ) {}

    public function index()
    {
        $kpis = $this->statsService->kpis();
        $latestSubscriptions = $this->statsService->latestSubscriptions();
        $moduleChart = $this->statsService->moduleUsageChart();
        $plans = Plan::withCount('tenants')->orderBy('sort_order')->get();

        return view('platform.plans.index', compact(
            'kpis',
            'latestSubscriptions',
            'moduleChart',
            'plans'
        ));
    }

    public function create()
    {
        $plan = new Plan([
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);
        $modules = PlatformModule::where('is_active', true)->orderBy('sort_order')->get();
        $planModules = [];

        return view('platform.plans.form', [
            'plan' => $plan,
            'modules' => $modules,
            'planModules' => $planModules,
        ]);
    }

    public function store(StorePlanRequest $request)
    {
        $this->planService->create(
            $request->validated(),
            $request->input('modules', [])
        );

        return redirect()
            ->route('platform.plans.index')
            ->with('success', __('platform.plans.created'));
    }

    public function edit(Plan $plan)
    {
        $plan->load('planModules');
        $modules = PlatformModule::where('is_active', true)->orderBy('sort_order')->get();
        $planModules = $plan->planModules->keyBy('module_key');

        return view('platform.plans.form', compact('plan', 'modules', 'planModules'));
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $this->planService->update(
            $plan,
            $request->validated(),
            $request->input('modules', [])
        );

        return redirect()
            ->route('platform.plans.index')
            ->with('success', __('platform.plans.updated'));
    }

    public function destroy(Plan $plan)
    {
        if ($plan->tenants()->exists()) {
            return back()->with('error', __('platform.plans.cannot_delete_has_tenants'));
        }

        $plan->planModules()->delete();
        $plan->delete();

        return redirect()
            ->route('platform.plans.index')
            ->with('success', __('platform.plans.deleted'));
    }
}
