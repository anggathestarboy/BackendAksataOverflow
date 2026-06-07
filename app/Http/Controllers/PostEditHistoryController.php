<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostEditHistory;
use Illuminate\Http\Request;

class PostEditHistoryController extends Controller
{
    public function index($id) {

        $post = Post::where('id', $id)->first();
        if(!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }


        $histories = PostEditHistory::where('post_id', $id)->with('user', 'post')->get();
        return response()->json([
            'histories' => $histories,
        ]);
    }
}
