<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => Tenant::count(),
            'active' => Tenant::withApplicationAccess()->where('status', 'active')->count(),
            'trial' => Tenant::where('status', 'trial')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
            'expired' => Tenant::where('status', 'expired')->count(),
            'inactive' => Tenant::where('is_active', false)->count(),
        ];

        $recentTenants = Tenant::with('plan')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $byPlan = Tenant::query()
            ->select('plan_id', DB::raw('count(*) as total'))
            ->whereNotNull('plan_id')
            ->groupBy('plan_id')
            ->with('plan')
            ->get();

        $plansCount = Plan::where('is_active', true)->count();

        return view('platform.dashboard', compact('stats', 'recentTenants', 'byPlan', 'plansCount'));
    }
}
