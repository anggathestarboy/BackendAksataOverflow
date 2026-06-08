<?php

namespace App\Observers;

use App\Models\User;
use App\Services\BadgeService;

class UserObserver
{
    protected BadgeService $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->badgeService->awardBadgesForUser($user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $this->badgeService->awardBadgesForUser($user);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Optionally delete user badges when user is deleted
        $user->badges()->detach();
    }
}
