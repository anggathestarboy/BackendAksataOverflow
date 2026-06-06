<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Str;

class MentionService
{
    /**
     * Extract usernames mentioned with @ and create notifications.
     *
     * @param string $text The text to parse.
     * @param int|string $actorId ID of the user who performed the action.
     * @param string $type Type of notification, e.g., 'mention'.
     * @param int|string $referenceId ID of the related post or comment.
     * @param string $referenceType 'post' or 'comment'.
     */
    public static function handleMentions(string $text, $actorId, string $type, $referenceId, string $referenceType)
    {
        // Find all @username patterns
        preg_match_all('/@([A-Za-z0-9_]+)/', $text, $matches);
        $usernames = $matches[1] ?? [];
        if (empty($usernames)) {
            return;
        }
        // Unique usernames
        $usernames = array_unique($usernames);
        // Fetch user models
        $users = User::whereIn('username', $usernames)->get();
        foreach ($users as $user) {
            // Avoid notifying self
            if ($user->id == $actorId) {
                continue;
            }
            Notification::create([
                'user_id' => $user->id, // recipient
                'actor_id' => $actorId,
                'type' => $type,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType,
                'is_read' => false,
                // created_at will default to now via migration
            ]);
        }
    }
}
