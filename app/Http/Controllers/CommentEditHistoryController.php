<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentEditHistory;
use Illuminate\Http\Request;

class CommentEditHistoryController extends Controller
{
    public function index($id) {

        $komentar = Comment::where('id', $id)->first();
        if(!$komentar) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }


        $histories = CommentEditHistory::where('comment_id', $id)->with('user', 'comment')->get();
        return response()->json([
            'histories' => $histories,
        ]);
    }
}
