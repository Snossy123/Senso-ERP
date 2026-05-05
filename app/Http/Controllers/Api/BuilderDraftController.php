<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BuilderDraft;
use Illuminate\Http\Request;

class BuilderDraftController extends Controller
{
    public function latest()
    {
        $draft = BuilderDraft::latest()->first();

        if (! $draft) {
            return response()->json([
                'success' => false,
                'message' => 'No drafts found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'draft' => $draft,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|array',
            'name' => 'nullable|string',
        ]);

        $draft = BuilderDraft::create([
            'name' => $validated['name'] ?? 'Saved Draft '.now()->toDateTimeString(),
            'content' => $validated['content'],
        ]);

        return response()->json([
            'success' => true,
            'draft' => $draft,
        ], 201);
    }
}
