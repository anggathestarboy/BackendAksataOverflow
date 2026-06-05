<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookmarks = Bookmark::with('post')->where('user_id', auth()->id())->get();
        return response()->json(["message" => "Bookmarks retrieved successfully", "data" => $bookmarks]);
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
            'post_id' => 'required|exists:posts,id',
        ]);

        if (Bookmark::where('user_id', $user->id)->where('post_id', $request->post_id)->exists()) {
            return response()->json(["message" => "You have already bookmarked this post"], 400);
        }

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $request->post_id,
        ]);
        return response()->json(["message" => "Post bookmarked successfully", "data" => $bookmark], 201);
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
        $bookmark = Bookmark::where('id', $id)->first();
$user = auth()->user();
        

        if (!$bookmark) {
            return response()->json(["message" => "Bookmark not found"], 404);
        }


        if ($bookmark->user_id !== $user->id) {
            return response()->json(["message" => "You do not have permission to delete this bookmark"], 403);
        }





        $bookmark->delete();
        return response()->json(["message" => "Bookmark removed successfully"]);
    }
}
