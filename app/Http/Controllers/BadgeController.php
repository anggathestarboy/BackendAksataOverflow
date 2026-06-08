<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    protected BadgeService $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $badges = Badge::orderBy('tier')->orderBy('condition_value')->get();

        return response()->json([
            'success' => true,
            'data'    => $badges,
        ]);
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
        $data = $request->validate([
            'name'            => 'required|string|max:255|unique:badges,name',
            'description'     => 'nullable|string',
            'icon_url'        => 'nullable|file|image|max:2048|mimes:jpeg,png,jpg',
            'tier'            => 'required|in:bronze,silver,gold,platinum',
            'condition_type'  => 'required|string|in:reputation_points,posts_count,answers_accepted,comments_count,bookmarks_count,followers_count',
            'condition_value' => 'required|integer',
        ]);

        if ($request->hasFile('icon_url')) {
            $data['icon_url'] = $request->file('icon_url')
                ->store('badges', 'public');
        }

        $badge = Badge::create($data);

        return response()->json([
            'message' => 'Badge created successfully',
            'data'    => $badge,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Badge $badge)
    {
        $users = $badge->users()->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => [
                'badge' => $badge,
                'users' => $users,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Badge $badge)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $badge_id)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255|unique:badges,name',
            'description'     => 'nullable|string',
            'icon_url'        => 'nullable|file|image|max:2048|mimes:jpeg,png,jpg',
            'tier'            => 'required|in:bronze,silver,gold,platinum',
            'condition_type'  => 'required|string|in:reputation_points,posts_count,answers_accepted,comments_count,bookmarks_count,followers_count',
            'condition_value' => 'required|integer',
        ]);

        if ($request->hasFile('icon_url')) {
            $data['icon_url'] = $request->file('icon_url')
                ->store('badges', 'public');
        }

        $badge = Badge::find($badge_id);

        if (!$badge) {
            return response()->json([
                'message' => 'Badge not found',
            ], 404);
        }

        $badge->update($data);

        return response()->json([
            'message' => 'Badge updated successfully',
            'data'    => $badge,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $badge_id)
    {
        $badge = Badge::find($badge_id);

        if (!$badge) {
            return response()->json([
                'message' => 'Badge not found',
            ], 404);
        }

        $badge->delete();

        return response()->json([
            'message' => 'Badge deleted successfully',
        ]);
    }

    /**
     * Get user's badges
     */
    public function getUserBadges(Request $request, User $user)
    {
        $badges = $this->badgeService->getUserBadges($user);

        return response()->json([
            'success' => true,
            'data'    => $badges,
        ]);
    }

    /**
     * Get upcoming badges for a user
     */
    public function getUpcomingBadges(Request $request, User $user)
    {
        $upcomingBadges = $this->badgeService->getUpcomingBadges($user);

        return response()->json([
            'success' => true,
            'data'    => $upcomingBadges,
        ]);
    }
}
