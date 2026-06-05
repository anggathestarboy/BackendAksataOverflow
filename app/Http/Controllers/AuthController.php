<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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



        return response()->json(['message' => 'User registered successfully', 'user' => $user->load('roles')], 201);
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


    public function jadikanAdmin ($username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            return response()->json(['message' => 'Admin role not found'], 404);
        }

        if( UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
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


    public function turunkanJabatan($username) {
        $user = User::where('username', $username)->first();

        if($user && !UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'moderator']);
        })->exists()) {
            return response()->json(['message' => 'User is already a regular user'], 400);
        }

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if( UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->exists()) {
           UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
                $query->where('name', 'admin');
            })->delete();
        }

        if( UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
            $query->where('name', 'moderator');
        })->exists()) {
           UserRole::where('user_id', $user->id)->whereHas('role', function ($query) {
                $query->where('name', 'moderator');
            })->delete();
        }

        return response()->json(['message' => 'Your role has been downgraded to user']);
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


      

        return response()->json([
            'message' => 'Profile updated successfully',
        ], 200);
    }
}
