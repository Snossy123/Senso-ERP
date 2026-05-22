<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformModule;
use Illuminate\Http\Request;

class PlatformModuleController extends Controller
{
    public function index()
    {
        $modules = PlatformModule::orderBy('sort_order')->get();

        return view('platform.modules.index', compact('modules'));
    }

    public function update(Request $request, PlatformModule $module)
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $module->update($validated);

        return back()->with('success', __('platform.modules.updated'));
    }
}
