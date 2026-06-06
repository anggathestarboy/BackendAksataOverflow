<?php

namespace App\Services;

use App\Models\PointsLog;
use App\Models\User;
use Illuminate\Support\Str;

class ReputationService
{
    // ── Point values ────────────────────────────────────────────────────────────
    const POINTS_POST_CREATED      = 1;
    const POINTS_POST_UPVOTED      = 2;
    const POINTS_COMMENT_UPVOTED   = 3;
    const POINTS_ANSWER_ACCEPTED   = 5;
    const POINTS_DOWNVOTED         = 2;   // deducted from content owner
    const POINTS_REPORT_RESOLVED   = 6;   // deducted when a report is resolved

    // ── Action type slugs ────────────────────────────────────────────────────────
    const ACTION_POST_CREATED      = 'post_created';
    const ACTION_POST_UPVOTED      = 'post_upvoted';
    const ACTION_COMMENT_UPVOTED   = 'comment_upvoted';
    const ACTION_ANSWER_ACCEPTED   = 'answer_accepted';
    const ACTION_POST_DOWNVOTED    = 'post_downvoted';
    const ACTION_COMMENT_DOWNVOTED = 'comment_downvoted';
    const ACTION_REPORT_RESOLVED   = 'report_resolved';

    // Level thresholds
    // Level 1 :  0 – 9   pts  (also any negative value → still level 1)
    // Level 2 : 10 – 19  pts
    // Level 3 : 20 – 29  pts
    // Level n : (n-1)*10 – n*10-1 pts
    public static function calculateLevel(int $points): int
    {
        if ($points < 10) {
            return 1;
        }
        return (int) floor($points / 10) + 1;
    }

    /**
     * Award (or deduct when $points is negative) reputation points,
     * log the action, and automatically recalculate the user's level.
     *
     * @param  string      $userId
     * @param  int         $points      Positive = earn, negative = deduct
     * @param  string      $actionType  One of the ACTION_* constants
     * @param  string|null $referenceId Related post/comment UUID
     * @param  string|null $description Human-readable description
     * @return PointsLog
     */
    public static function award(
        string  $userId,
        int     $points,
        string  $actionType,
        ?string $referenceId = null,
        ?string $description = null
    ): PointsLog {
        // Atomically update reputation_points — allow going negative
        User::where('id', $userId)->increment('reputation_points', $points);

        // Recalculate and persist the level
        $user  = User::find($userId);
        $level = self::calculateLevel($user->reputation_points);
        if ($user->level !== $level) {
            $user->update(['level' => $level]);
        }

        // Log the action
        return PointsLog::create([
            'id'           => (string) Str::uuid(),
            'user_id'      => $userId,
            'points'       => $points,
            'action_type'  => $actionType,
            'reference_id' => $referenceId,
            'description'  => $description,
        ]);
    }

    /**
     * Convenience wrapper — deduct a positive amount.
     */
    public static function deduct(
        string  $userId,
        int     $points,
        string  $actionType,
        ?string $referenceId = null,
        ?string $description = null
    ): PointsLog {
        return self::award($userId, -abs($points), $actionType, $referenceId, $description);
    }
}
