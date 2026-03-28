<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin()) {
                abort(403, 'Access denied. Admin only.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Activity::with('user')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(30)->withQueryString();
        $types = Activity::distinct()->pluck('type');
        $users = Activity::with('user')->get()->pluck('user.name', 'user.id')->filter()->unique();

        return view('admin.activity-log.index', compact('activities', 'types', 'users'));
    }

    public function show(Activity $activity)
    {
        $activity->load('user');
        return view('admin.activity-log.show', compact('activity'));
    }

    public function userActivity($userId)
    {
        $activities = Activity::where('user_id', $userId)->latest()->paginate(30);
        return view('admin.activity-log.index', compact('activities'));
    }
}
