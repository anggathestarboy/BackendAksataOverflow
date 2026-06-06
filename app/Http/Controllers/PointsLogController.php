<?php

namespace App\Http\Controllers;

use App\Models\PointsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointsLogController extends Controller
{
    /**
     * GET /points-logs
     * Returns the authenticated user's reputation points history (paginated).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $logs = PointsLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status'             => 'success',
            'reputation_points'  => $user->reputation_points,
            'data'               => $logs,
        ]);
    }

    /**
     * GET /points-logs/user/{username}
     * Returns a specific user's public reputation history (paginated).
     */
    public function byUser(Request $request, string $username)
    {
        $target = \App\Models\User::where('username', $username)->firstOrFail();

        $logs = PointsLog::where('user_id', $target->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status'             => 'success',
            'username'           => $target->username,
            'reputation_points'  => $target->reputation_points,
            'data'               => $logs,
        ]);
    }
}
