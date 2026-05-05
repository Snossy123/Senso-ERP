<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->isAdmin()) {
                abort(403, 'Access denied. Admin only.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Activity::with('user')->orderBy('created_at', 'desc');

        if ($request->user()->tenant_id !== null) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        // Search/Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('model_type')) {
            $query->where('model_type', 'like', '%'.$request->model_type.'%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('export')) {
            return $this->exportCSV($query->get());
        }

        $activities = $query->paginate(30)->withQueryString();

        $usersQuery = User::query();
        if ($request->user()->tenant_id !== null) {
            $usersQuery->where('tenant_id', $request->user()->tenant_id);
        }
        $users = $usersQuery->pluck('name', 'id');
        $types = Activity::distinct('type')->pluck('type');

        return view('admin.activity-log.index', compact('activities', 'users', 'types'));
    }

    public function show(Activity $activity)
    {
        $activity->load('user');

        return view('admin.activity-log.show', compact('activity'));
    }

    protected function exportCSV($logs)
    {
        $filename = 'activity_logs_'.now()->format('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return Response::stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            // Add headers
            fputcsv($handle, ['ID', 'Date', 'User', 'Type', 'Action', 'Severity', 'Description', 'Model', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at->toDateTimeString(),
                    $log->user?->name ?? 'System',
                    $log->type,
                    $log->action,
                    $log->severity,
                    $log->description,
                    $log->model_type ? basename($log->model_type)." #{$log->model_id}" : 'N/A',
                    $log->ip_address,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
