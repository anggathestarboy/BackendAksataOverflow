<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($username)
    {
          $user = User::where('username', $username)->first();

          if(!$user) {
            return response()->json(['message' => 'User not found'], 404);
          }

          $likes = Like::where('user_id', $user->id)->with(['post', 'comment'])->get();
          return response()->json(['likes' => $likes]);
    }


    /**
 * Get likes by category slug
 */
public function getByCategory(Request $request, string $categorySlug)
{
    $user = $request->user();

    $category = \App\Models\Category::where('slug', $categorySlug)->first();

    if (!$category) {
        return response()->json(['message' => 'Category not found'], 404);
    }

    $likes = Like::where('user_id', $user->id)
        ->where('target_type', 'post')
        ->whereHasMorph('target', [Post::class], function ($query) use ($category) {
            $query->where('category_id', $category->id)
                  ->where('status', '!=', 'deleted');
        })
        ->with(['post'])
        ->get();

    return response()->json([
        'category' => $categorySlug,
        'likes' => $likes,
    ]);
}

/**
 * Get likes by tag slug
 */
public function getByTag(Request $request, string $tagSlug)
{
    $user = $request->user();

    $tag = \App\Models\Tag::where('slug', $tagSlug)->first();

    if (!$tag) {
        return response()->json(['message' => 'Tag not found'], 404);
    }

    $likes = Like::where('user_id', $user->id)
        ->where('target_type', 'post')
        ->whereHasMorph('target', [Post::class], function ($query) use ($tag) {
            $query->whereHas('tags', fn($q) => $q->where('tags.id', $tag->id))
                  ->where('status', '!=', 'deleted');
        })
        ->with(['post'])
        ->get();

    return response()->json([
        'tag' => $tagSlug,
        'likes' => $likes,
    ]);
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
        $user = $request->user();
        $request->validate([
            'target_id' => 'required|uuid',
        ]);

        $dataLike = $request->target_id;
        if( $post = Post::find($dataLike) ) {
            $targetType = 'post';

            if ($post->status === 'deleted') {
                return response()->json(["message" => "Cannot like a deleted post"], 403);
            }

            if (Like::where('user_id', $user->id)->where('target_id', $dataLike)->where('target_type', $targetType)->exists()) {
                return response()->json(["message" => "You have already liked this post"], 400);
            }

           Like::create([
                'user_id' => $user->id,
                'target_id' => $dataLike,
                'target_type' => $targetType,
            ]);

            // Kirim notifikasi ke pemilik post (jika bukan diri sendiri)
            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'actor_id' => $user->id,
                    'type' => 'like',
                    'reference_id' => $post->id,
                    'reference_type' => 'post',
                ]);
            }

            return response()->json(["message" => "Post liked successfully", "data" => $post]);
        } else if ( $comment = Comment::find($dataLike)) {
            $targetType = 'comment';

            // Check if comment belongs to a deleted post
            $commentPost = Post::find($comment->post_id);
            if ($commentPost && $commentPost->status === 'deleted') {
                return response()->json(["message" => "Cannot like a comment on a deleted post"], 403);
            }

            if (Like::where('user_id', $user->id)->where('target_id', $dataLike)->where('target_type', $targetType)->exists()) {
                return response()->json(["message" => "You have already liked this comment"], 400);
            }

            Like::create([
                'user_id' => $user->id,
                'target_id' => $dataLike,
                'target_type' => $targetType,
            ]);

            // Kirim notifikasi ke pemilik comment (jika bukan diri sendiri)
            if ($comment->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $comment->user_id,
                    'actor_id' => $user->id,
                    'type' => 'like',
                    'reference_id' => $comment->id,
                    'reference_type' => 'comment',
                ]);
            }

            return response()->json(["message" => "Comment liked successfully", "data" => $comment]);
        } else {
            return response()->json(["message" => "Target not found"], 404);
        }
    }


    public function unlike(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'target_id' => 'required|uuid',
        ]);

        $dataLike = $request->target_id;
        if( Post::find($dataLike) ) {
            $targetType = 'post';
        } else if ( Comment::find($dataLike)) {
            $targetType = 'comment';
        } else {
            return response()->json(["message" => "Target not found"], 404);
        }

        $like = Like::where('user_id', $user->id)->where('target_id', $dataLike)->where('target_type', $targetType)->first();
        if (!$like) {
            return response()->json(["message" => "You have not liked this target"], 400);
        }

        $like->delete();
        return response()->json(["message" => "Unliked successfully"]);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
