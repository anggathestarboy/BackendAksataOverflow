<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentEditHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $comments = Comment::with(['user', 'replies'])->whereNull('parent_id')->orderBy('created_at', 'desc')->get();
        return response()->json(["message" => "Comments retrieved successfully", "data" => $comments]);
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
        $user = Auth::user();
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'body' => 'required|string',
            "parent_id" => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $request->post_id,
            "parent_id" => $request->parent_id,
            'body' => $request->body,
        ]);

        return response()->json(["message" => "Comment created successfully", "data" => $comment], 201);
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
        'body' => 'required|string',
    ]);

    $comment = Comment::where('id', $id)->first();
    $bodyBefore = $comment?->body;
    if (!$comment) {
        return response()->json([
            'message' => 'Comment not found'
        ], 404);
    }

    
      $isOwner = $comment->user_id === $user->id;
        $isAdmin = $user->roles()->where('name', 'admin')->exists();
        if (!$isOwner && !$isAdmin) {
            return response()->json(['message' => 'you cannot update this comment'], 403);
        }

 $editCount = CommentEditHistory::where('comment_id', $comment->id)->count();

        if ($editCount >= 3) {
            return response()->json([
                'message' => 'This comment has reached the maximum edit limit (3 times)'
            ], 403);
        }

    $comment->update([
        'body' => $request->body,
    ]);

  $commentEditHistory = CommentEditHistory::create([
        'comment_id' => $comment->id,
        'edited_by' => $user->id,
        "body_before" => $bodyBefore,
        "body_after" => $comment->body,
        'edited_at' => now(),
    ]);


    

    return response()->json([
        'message' => 'Comment updated successfully',
        'data' => $comment->fresh(),
        "edit_history" => $commentEditHistory
    ]);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $comment = Comment::where('id', $id)->first();
        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ], 404);
        }

       
     

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully'
        ]);
    }
}
