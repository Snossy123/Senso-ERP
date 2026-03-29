<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $units = Unit::latest()->get();
        return view('inventory.units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'short_name' => 'required|string|max:20',
        ]);
        Unit::create($data);
        return redirect()->back()->with('success', 'Unit created.');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return redirect()->back()->with('success', 'Unit deleted.');
    }
}
