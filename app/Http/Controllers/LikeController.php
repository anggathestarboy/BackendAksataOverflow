<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
