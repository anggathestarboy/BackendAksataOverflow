<?php

namespace App\Console\Commands;

use App\Services\BadgeService;
use Illuminate\Console\Command;

class AwardBadgesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'badges:award';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Award badges to all users based on their current achievements';

    /**
     * Execute the console command.
     */
    public function handle(BadgeService $badgeService)
    {
        $this->info('Starting badge award process...');

        $badgeService->awardBadgesForAllUsers();

        $this->info('Badge award process completed successfully!');
    }
}
