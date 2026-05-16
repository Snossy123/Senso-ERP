<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerTag;
use Illuminate\Http\Request;

class CustomerTagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $tags = CustomerTag::withCount('customers')->orderBy('name')->get();

        return view('crm.tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'nullable|string|max:20',
        ]);

        CustomerTag::create([
            'name' => $data['name'],
            'color' => $data['color'] ?? '#6366f1',
        ]);

        return back()->with('success', 'Tag created.');
    }

    public function destroy(CustomerTag $tag)
    {
        $tag->delete();

        return back()->with('success', 'Tag removed.');
    }
}
