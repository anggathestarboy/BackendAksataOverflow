<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tags = Tag::all();
        return response()->json(["message" => "Tags retrieved successfully", "data" => $tags]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            "color" => 'nullable|string',
        ]);

        $slug = str()->slug($request->name);
        $tag = Tag::create([
            'name' => $request->name,
            'slug' => $slug,
            'color' => $request->color,
        ]);
        return response()->json(["message" => "Tag created successfully", "data" => $tag], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            "color" => 'nullable|string',
        ]);

        $tag = Tag::where("slug", $slug)->first();
        if (!$tag) {
            return response()->json(["message" => "Tag not found"], 404);
        }
        $tag->name = $request->name;
        $tag->color = $request->color;
        $tag->slug = str()->slug($request->name);
        $tag->save();

        return response()->json(["message" => "Tag updated successfully", "data" => $tag]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slug)
    {
        $tag = Tag::where("slug", $slug)->first();
        if (!$tag) {
            return response()->json(["message" => "Tag not found"], 404);
        }
        $tag->delete();
        return response()->json(["message" => "Tag deleted successfully"]);
    }
}
