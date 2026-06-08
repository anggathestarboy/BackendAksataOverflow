<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    public function awardBadgesForUser(User $user): void
    {
        $badges = Badge::all();

        foreach ($badges as $badge) {
            $userHasBadge = $user->badges()
                ->where('badge_id', $badge->id)
                ->exists();

            if (!$userHasBadge && $this->userMeetsCondition($user, $badge)) {
                $this->awardBadge($user, $badge); // ← fix: pakai method ini
            }
        }
    }

    public function awardBadge(User $user, Badge $badge): bool
    {
        if ($user->hasBadge($badge->id)) {
            return false;
        }

        UserBadge::create([
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);

        return true;
    }

    private function userMeetsCondition(User $user, Badge $badge): bool
    {
        return match ($badge->condition_type) {
            'reputation_points' => $user->reputation_points >= $badge->condition_value,
            'posts_count'       => $user->posts()->count() >= $badge->condition_value,
            'answers_accepted'  => $this->getAcceptedAnswersCount($user) >= $badge->condition_value,
            'comments_count'    => $user->comments()->count() >= $badge->condition_value,
            'bookmarks_count'   => $user->bookmarks()->count() >= $badge->condition_value,
            'followers_count'   => $user->followers()->count() >= $badge->condition_value,
            default             => false,
        };
    }

    private function getAcceptedAnswersCount(User $user): int
    {
        return DB::table('comments')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->where('comments.user_id', $user->id)
            ->where('posts.accepted_answer_id', DB::raw('comments.id'))
            ->count();
    }

    public function awardBadgesForAllUsers(): void
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $this->awardBadgesForUser($user);
            }
        });
    }

    public function getUserBadges(User $user)
    {
        return $user->badges()
            ->orderBy('tier')
            ->get();
    }

    public function getUpcomingBadges(User $user)
    {
        $badges = Badge::all();
        $upcoming = [];

        foreach ($badges as $badge) {
            $userHasBadge = $user->badges()
                ->where('badge_id', $badge->id)
                ->exists();

            if (!$userHasBadge) {
                $progress = $this->getProgressTowardsBadge($user, $badge);
                if ($progress['percentage'] > 0) {
                    $upcoming[] = [
                        'badge'    => $badge,
                        'progress' => $progress,
                    ];
                }
            }
        }

        return collect($upcoming)
            ->sortByDesc('progress.percentage')
            ->values();
    }

    private function getProgressTowardsBadge(User $user, Badge $badge): array
    {
        $currentValue = match ($badge->condition_type) {
            'reputation_points' => $user->reputation_points ?? 0,
            'posts_count'       => $user->posts()->count(),
            'answers_accepted'  => $this->getAcceptedAnswersCount($user),
            'comments_count'    => $user->comments()->count(),
            'bookmarks_count'   => $user->bookmarks()->count(),
            'followers_count'   => $user->followers()->count(),
            default             => 0,
        };

        $percentage = min(100, round(($currentValue / $badge->condition_value) * 100, 2));

        return [
            'current'    => $currentValue,
            'required'   => $badge->condition_value,
            'percentage' => $percentage,
        ];
    }
}