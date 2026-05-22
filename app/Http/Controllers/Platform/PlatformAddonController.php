<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAddon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlatformAddonController extends Controller
{
    public function index()
    {
        $addons = PlatformAddon::orderBy('name')->paginate(20);

        return view('platform.addons.index', compact('addons'));
    }

    public function create()
    {
        return view('platform.addons.form', ['addon' => new PlatformAddon]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAddon($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        PlatformAddon::create($data);

        return redirect()->route('platform.addons.index')->with('success', __('platform.addons.created'));
    }

    public function edit(PlatformAddon $addon)
    {
        return view('platform.addons.form', compact('addon'));
    }

    public function update(Request $request, PlatformAddon $addon)
    {
        $addon->update($this->validateAddon($request));

        return redirect()->route('platform.addons.index')->with('success', __('platform.addons.updated'));
    }

    public function destroy(PlatformAddon $addon)
    {
        $addon->delete();

        return redirect()->route('platform.addons.index')->with('success', __('platform.addons.deleted'));
    }

    protected function validateAddon(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
