<?php

namespace App\Http\Controllers;

use App\Models\ModerationLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ReputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\MentionService;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $request->validate([
        'username' => 'required|string|max:12|unique:users,username|regex:/^[a-zA-Z0-9_]+$/',
        'email' => 'required|string|email|max:255|unique:users,email',
        'password_hash' => 'required|string|min:6',
        "avatar_url" => 'nullable|string|max:500',
        "bio" => 'nullable|string',
    ]);

    $user = User::create($request->all());

    UserRole::create([
        'user_id' => $user->id,
        'role_id' => Role::where('name', 'user')->first()->id,
    ]);

    // Auto-login setelah register
    $token = Auth::attempt([
        'username' => $request->username,
        'password' => $request->password_hash,
    ]);

    return response()->json([
        'message' => 'User registered successfully',
        'token' => $token,
        'user' => $user->load('roles'),
    ], 201);
}


    public function login(Request $request)
    {
        $validasi = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['message' => 'Username not found'], 401);
        }


        if (!$token = Auth::attempt($validasi)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }



        return response()->json(['message' => 'Login successful', 'token' => $token, "user" => $user->load('roles')], 200);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Successfully logged out']);
    }



    public function resetPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!password_verify($request->current_password, $user->password_hash)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        $user->password_hash = $request->new_password;
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function me()
    {
        $user = Auth::user()->load('roles');

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'bio' => $user->bio,
            'reputation_points' => $user->reputation_points,
            'level' => $user->level,
            'is_banned' => $user->is_banned,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'role' => $user->roles,
        ]);
    }

    public function jadikanModerator($username)
    {

        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $moderatorRole = Role::where('name', 'moderator')->first();
        if (!$moderatorRole) {
            return response()->json(['message' => 'Moderator role not found'], 404);
        }

        if (UserRole::where('user_id', $user->id)->where('role_id', $moderatorRole->id)->exists()) {
            return response()->json(['message' => 'User is already a moderator'], 400);
        }

        if (UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->exists()) {
            return response()->json(['message' => 'User is already an admin, cannot be promoted to moderator'], 400);
        }

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $moderatorRole->id,
        ]);

        return response()->json(['message' => 'User has been promoted to moderator']);
    }


    public function getAllUser()
    {
        $users = User::all();
        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * GET /users/{username}/level
     * Public endpoint — returns level info and progress for any user.
     */
    public function getLevelInfo(string $username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $pts  = $user->reputation_points; // allow negative
        $level = ReputationService::calculateLevel($pts);

        // For progress calculation, level 1 floor is always 0 (even if pts < 0)
        $currentFloor    = max(0, ($level - 1) * 10);
        $nextFloor       = $level * 10;
        $progressInLevel = max(0, $pts - $currentFloor);
        $pointsToNext    = $nextFloor - $pts;

        return response()->json([
            'status'            => 'success',
            'username'          => $user->username,
            'reputation_points' => $pts,
            'level'             => $level,
            'progress'          => [
                'current_level_floor'   => $currentFloor,
                'next_level_floor'      => $nextFloor,
                'points_in_this_level'  => $progressInLevel,
                'points_to_next_level'  => $pointsToNext,
                'percent'               => round(($progressInLevel / 10) * 100, 1),
            ],
        ]);
    }


    public function jadikanAdmin($username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            return response()->json(['message' => 'Admin role not found'], 404);
        }

        if (UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
            $query->where('name', 'moderator');
        })->exists()) {
            UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
                $query->where('name', 'moderator');
            })->delete();
        }


        if (UserRole::where('user_id', $user->id)->where('role_id', $adminRole->id)->exists()) {
            return response()->json(['message' => 'User is already an admin'], 400);
        }

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $adminRole->id,
        ]);

        return response()->json(['message' => 'User has been promoted to admin']);
    }


    public function turunkanJabatan($username)
    {
        $user = User::where('username', $username)->first();

        if ($user && !UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'moderator']);
        })->exists()) {
            return response()->json(['message' => 'User is already a regular user'], 400);
        }

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->exists()) {
            UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
                $query->where('name', 'admin');
            })->delete();
        }

        if (UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
            $query->where('name', 'moderator');
        })->exists()) {
            UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
                $query->where('name', 'moderator');
            })->delete();
        }

        return response()->json(['message' => 'Your role has been downgraded to user']);
    }


    public function getDetailUser($username)
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $currentUser = auth()->user();

        $user->load([
            'roles',
            'badges' => function ($query) {
                $query->select('badges.id', 'badges.name', 'badges.description', 'badges.icon_url', 'badges.tier', 'badges.condition_type', 'badges.condition_value')
                    ->orderByPivot('created_at', 'desc');
            },
            'posts' => function ($query) {
                $query->where('status', '!=', 'deleted')
                    ->withCount([
                        'likes',
                        'bookmarks',
                        'comments',
                        'votes as upvotes_count' => fn($q) => $q->where('vote_type', 'upvote'),
                        'votes as downvotes_count' => fn($q) => $q->where('vote_type', 'downvote'),
                    ])
                    ->with(['tags', 'category', 'user']);
            },
        ])->loadCount([
            'posts' => function ($query) {
                $query->where('status', '!=', 'deleted');
            },
            'followers',
            'following',
            'badges',
        ]);

        $isFollowing = $currentUser
            ? $currentUser->following()->where('following_id', $user->id)->exists()
            : false;

        $user->posts->transform(function ($post) use ($currentUser) {
            $post->votes_count = $post->upvotes_count - $post->downvotes_count;
            $post->user_has_liked = $currentUser
                ? $post->likes()->where('user_id', $currentUser->id)->exists()
                : false;
            $post->user_has_bookmarked = $currentUser
                ? $post->bookmarks()->where('user_id', $currentUser->id)->exists()
                : false;

            return $post;
        });

        return response()->json([
            'message'      => 'Success get user detail',
            'user'         => $user,
            'is_following' => $isFollowing,
        ]);
    }


    public function banUser(Request $request, $username)
    {

    $request->validate([
        'reason' => 'required|string|max:255',
        "notes" => 'nullable|string|max:500',
    ]);
        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->is_banned) {
            return response()->json(['message' => 'User is already banned'], 400);
        }

        $user->is_banned = true;
        $user->save();

        ModerationLog::create([
            'moderator_id' => auth()->id(),
            'target_user_id' => $user->id,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'action_type' => 'ban',
        ]);

        return response()->json(['message' => 'User has been banned']);
    }


    public function unbanUser(Request $request, $username)
    {
    $request->validate([
        'reason' => 'required|string|max:255',
        "notes" => 'nullable|string|max:500',
    ]);


        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        if (!$user->is_banned) {
            return response()->json(['message' => 'User is not banned'], 400);
        }
        $user->is_banned = false;
        $user->save();

        ModerationLog::create([
            'moderator_id' => auth()->id(),
            'target_user_id' => $user->id,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'action_type' => 'unban',
        ]);
        return response()->json(['message' => 'User has been unbanned']);
    }





    public function createWarning(Request $request, $username)
    {
        $userLogin = auth()->user();
        $request->validate([
            'reason' => 'required|string|max:255',
            "notes" => 'nullable|string|max:500',
        ]);

        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Simpan warning ke database (misalnya ke tabel warnings)
        ModerationLog::create([
            "moderator_id" => $userLogin->id,
            'target_user_id' => $user->id,
            'reason' => $request->reason,
            'action_type' => 'warning',
            'notes' => $request->notes,

        ]);

        return response()->json(['message' => 'Warning issued to user']);
    }


    public function getAllModerationLogs()
    {
        $logs = ModerationLog::with(['moderator', "user"])->orderBy('created_at', 'desc')->get();
        return response()->json([
            'message' => 'Success get all moderation logs',
            'data' => $logs,
        ]);
    }


    public function getModerationLogsByUser()
    {
        $user = Auth::user();

        $logs = ModerationLog::with(['moderator', "user"])
            ->where('target_user_id', $user->id)->where('action_type', 'warning')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Success get user moderation logs',
            'data' => $logs,
        ]);
    }



    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data =  $request->validate([
            'username'   => 'required|string|max:12|unique:users,username,' . $user->id . '|regex:/^[a-zA-Z0-9_]+$/',
            'email'      => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Diubah menjadi validasi gambar (max 2MB)
            'bio'        => 'nullable|string',
        ]);





        if ($request->hasFile('avatar_url')) {


            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }


            $path = $request->file('avatar_url')->store('avatars', 'public');


            $data['avatar_url'] = $path;
        }


        $user->update($data);

        // Handle @mentions in bio
        if ($request->filled('bio')) {
            MentionService::handleMentions($request->bio, $user->id, 'mention', $user->id, 'user');
        }

        return response()->json([
            'message' => 'Profile updated successfully',
        ], 200);
    }
}
