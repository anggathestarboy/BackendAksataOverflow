<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function leaderboard()
    {
        $users = User::orderBy('reputation_points', 'desc')
            ->take(10)
            ->get();

        return response()->json($users);
    }
}
