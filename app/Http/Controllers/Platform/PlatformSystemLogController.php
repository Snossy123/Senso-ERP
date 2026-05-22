<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Tenant;
use App\Scopes\TenantScope;
use Illuminate\Http\Request;

class PlatformSystemLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::withoutGlobalScope(TenantScope::class)
            ->with(['user', 'tenant'])
            ->orderByDesc('created_at');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(30)->withQueryString();
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return view('platform.logs.index', compact('logs', 'tenants'));
    }
}
