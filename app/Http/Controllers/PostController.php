<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostEditHistory;
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
            "reason" => 'required|string',
            'category_slug' => 'required|exists:categories,slug',
            'tags' => 'array',
            'tags.*' => 'exists:tags,slug',
        ]);

        $post = Post::where('id', $id)->first();
        $bodyBefore = $post?->body;

        if (!$post) {
            return response()->json(['message' => 'post not found'], 404);
        }

        $isOwner = $post->user_id === $user->id;
        $isAdmin = $user->roles()->where('name', 'admin')->exists();
        $isModerator = $user->roles()->where('name', 'moderator')->exists();

        if (!$isOwner && !$isAdmin && !$isModerator) {
            return response()->json(['message' => 'you cannot update this post'], 403);
        }


        $editCount = PostEditHistory::where('post_id', $post->id)->count();

        if ($editCount >= 3) {
            return response()->json([
                'message' => 'This post has reached the maximum edit limit (3 times)'
            ], 403);
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

        $postEditHistory = PostEditHistory::create([
            'post_id' => $post->id,
            'edited_by' => $user->id,
            'reason' => $request->reason,
            "body_before" => $bodyBefore,
            "body_after" => $post->body,
        ]);




        return response()->json([
            'message' => 'post updated successfully',
            'data' => $post->fresh()->load('tags', 'category'),
            "edit_history" => $postEditHistory
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();



        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found'
            ], 404);
        }

        $isOwner = $post->user_id === $user->id;
        $isAdmin = $user->roles()->where('name', 'admin')->exists();
        $isModerator = $user->roles()->where('name', 'moderator')->exists();

        if (!$isOwner && !$isAdmin && !$isModerator) {
            return response()->json([
                'message' => 'You do not have permission to delete this post'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully'
        ]);
    }
}
