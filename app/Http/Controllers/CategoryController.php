<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        return response()->json(["message" => "Categories retrieved successfully", "data" => $categories]);
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
            "description" => 'nullable|string',
            "parent_id" => 'nullable|exists:categories,name',
        ]);


        $parentId = Category::where("name", $request->parent_id)->first()?->id;

        $slug = str()->slug($request->name);

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => $slug,
            'parent_id' => $parentId,
        ]);

        return response()->json(["message" => "Category created successfully", "data" => $category], 201);
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
    public function update(Request $request,string $slug)
    {
          $request->validate([
            'name' => 'required|string|max:255',
            "description" => 'nullable|string',
            "parent_id" => 'nullable|exists:categories,name',
        ]);


        

        $parentId = Category::where("name", $request->parent_id)->first()?->id;

      

        $category = Category::where("slug", $slug)->first();

        $slugUpdate = str()->slug($request->name);
        


        if (!$category) {
            return response()->json(["message" => "Category not found"], 404);
        }

        if($slugUpdate !== $slug){
            $slugExists = Category::where("slug", $slugUpdate)->exists();
            if ($slugExists) {
                return response()->json(["message" => "Category with the same name already exists"], 409);
            }
        }

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => $slugUpdate,
            'parent_id' => $parentId,
        ]);

        return response()->json(["message" => "Category updated successfully", "data" => $category]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slug)
    {
        $category = Category::where("slug", $slug)->first();

        if (!$category) {
            return response()->json(["message" => "Category not found"], 404);
        }

        $category->delete();

        return response()->json(["message" => "Category deleted successfully"]);
    }
}
