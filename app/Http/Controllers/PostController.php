<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::with(['tags', 'category', 'user'])->orderBy('created_at', 'desc')->paginate(10);
        return response()->json($posts);
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
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category_slug' => 'required|exists:categories,slug',
            'tags' => 'array',
            'tags.*' => 'exists:tags,slug',
        ]);

        $category = Category::where('slug', $request->category_slug)->first();



        $post = Post::create([
            'user_id' => auth()->id(),
            'category_id' => $category->id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        foreach ($request->tags as $tagSlug) {
            $tag = Tag::where('slug', $tagSlug)->first();


            if ($tag) {
                PostTag::create([
                    'post_id' => $post->id,
                    'tag_id' => $tag->id,
                ]);
            }
        }


        return response()->json([
            "message" => "post created successfully",
            "data" => $post->load('tags', 'category')
        ]);
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
    public function update(Request $request, string $id)
    {

        $user = Auth::user();
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category_slug' => 'required|exists:categories,slug',
            'tags' => 'array',
            'tags.*' => 'exists:tags,slug',
        ]);

        $post = Post::where('id', $id)->first();
        if (!$post) {
            return response()->json(['message' => 'post not found'], 404);
        }

        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'you are not the owner of this post'], 403);
        }

        $category = Category::where('slug', $request->category_slug)->first();

        $post->update([
            'category_id' => $category->id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        // hapus semua tag lama
        PostTag::where('post_id', $post->id)->delete();

        // tambahkan tag baru
        foreach ($request->tags ?? [] as $tagSlug) {
            $tag = Tag::where('slug', $tagSlug)->first();

            if ($tag) {
                PostTag::create([
                    'post_id' => $post->id,
                    'tag_id' => $tag->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'post updated successfully',
            'data' => $post->fresh()->load('tags', 'category')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $post = Post::where('id', $id)->first();
        if (!$post) {
            return response()->json(['message' => 'post not found'], 404);
        }
        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'you are not the owner of this post'], 403);
        }
        $post->delete();
        return response()->json(['message' => 'post deleted successfully']);
    }
}
