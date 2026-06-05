<?php

namespace App\Http\Controllers;

use App\Models\Comment;
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
    if (!$comment) {
        return response()->json([
            'message' => 'Comment not found'
        ], 404);
    }

    //  hanya pemilik komentar yang bisa edit
    if ($comment->user_id !== $user->id) {
        return response()->json([
            'message' => 'cannot edit others user comments'
        ], 403);
    }

    $comment->update([
        'body' => $request->body,
    ]);

    return response()->json([
        'message' => 'Comment updated successfully',
        'data' => $comment->fresh()
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
