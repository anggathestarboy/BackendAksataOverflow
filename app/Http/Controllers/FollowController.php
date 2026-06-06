<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function follow(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users,username',
        ]);
        $userToFollow = User::where('username', $request->username)->first();
        $currentUser = auth()->user();

        if ($currentUser->id === $userToFollow->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        if ($currentUser->following()->where('following_id', $userToFollow->id)->exists()) {
            return response()->json(['message' => 'You are already following this user'], 400);
        }

        $follow = Follow::create([
            'follower_id' => $currentUser->id,
            'following_id' => $userToFollow->id,
        ]);

        // Kirim notifikasi ke user yang di-follow
        Notification::create([
            'user_id' => $userToFollow->id,
            'actor_id' => $currentUser->id,
            'type' => 'follow',
            'reference_id' => null,
            'reference_type' => null,
        ]);

        return response()->json(['message' => 'Followed successfully', 'follow' => $follow], 201);
    }


    public function unfollow(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users,username',
        ]);
        $userToUnfollow = User::where('username', $request->username)->first();
        $currentUser = auth()->user();

        if ($currentUser->id === $userToUnfollow->id) {
            return response()->json(['message' => 'You cannot unfollow yourself'], 400);
        }

        $follow = Follow::where('follower_id', $currentUser->id)
            ->where('following_id', $userToUnfollow->id)
            ->first();

        if (!$follow) {
            return response()->json(['message' => 'You are not following this user'], 400);
        }

        $follow->delete();

        return response()->json(['message' => 'Unfollowed successfully'], 200);
    }


    public function followers($username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $followers = $user->followers()->get();

        return response()->json(['followers' => $followers], 200);
    }

    public function following($username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $following = $user->following()->get();

        return response()->json(['following' => $following], 200);
    }
}
