<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        return response()->json(Tag::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:tags,name',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $tag = Tag::create($validated);

        AuditLog::record('tag.created', $tag, [], $tag->toArray());

        return response()->json($tag, 201);
    }

    public function destroy(Tag $tag)
    {
        $old = $tag->toArray();
        $tag->delete();

        AuditLog::record('tag.deleted', $tag, $old, []);

        return response()->json(null, 204);
    }
}
