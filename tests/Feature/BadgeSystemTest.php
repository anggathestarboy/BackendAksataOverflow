<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeSystemTest extends TestCase
{
    use RefreshDatabase;

    protected BadgeService $badgeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->badgeService = app(BadgeService::class);
    }

    public function test_user_gets_badge_when_meeting_condition()
    {
        // Create test badge
        $badge = Badge::create([
            'id'               => \Illuminate\Support\Str::uuid(),
            'name'             => 'First Post',
            'description'      => 'Create your first post',
            'tier'             => 'bronze',
            'condition_type'   => 'posts_count',
            'condition_value'  => 1,
        ]);

        // Create user
        $user = User::factory()->create();

        // Create 1 post for user
        $user->posts()->create([
            'id'          => \Illuminate\Support\Str::uuid(),
            'category_id' => \Illuminate\Support\Str::uuid(),
            'title'       => 'Test Post',
            'body'        => 'Test body',
        ]);

        // Award badges
        $this->badgeService->awardBadgesForUser($user);

        // Assert user has badge
        $this->assertTrue($user->badges()->where('badge_id', $badge->id)->exists());
    }

    public function test_user_does_not_get_badge_without_condition()
    {
        // Create test badge
        $badge = Badge::create([
            'id'               => \Illuminate\Support\Str::uuid(),
            'name'             => 'Prolific Writer',
            'description'      => 'Create 100 posts',
            'tier'             => 'silver',
            'condition_type'   => 'posts_count',
            'condition_value'  => 100,
        ]);

        // Create user without posts
        $user = User::factory()->create();

        // Award badges
        $this->badgeService->awardBadgesForUser($user);

        // Assert user does not have badge
        $this->assertFalse($user->badges()->where('badge_id', $badge->id)->exists());
    }

    public function test_get_upcoming_badges()
    {
        // Create badges
        $badge1 = Badge::create([
            'id'               => \Illuminate\Support\Str::uuid(),
            'name'             => 'Contributor',
            'description'      => 'Create 10 posts',
            'tier'             => 'bronze',
            'condition_type'   => 'posts_count',
            'condition_value'  => 10,
        ]);

        // Create user with 5 posts
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $user->posts()->create([
                'id'          => \Illuminate\Support\Str::uuid(),
                'category_id' => \Illuminate\Support\Str::uuid(),
                'title'       => "Test Post {$i}",
                'body'        => 'Test body',
            ]);
        }

        // Get upcoming badges
        $upcoming = $this->badgeService->getUpcomingBadges($user);

        // Assert badge is in upcoming
        $this->assertCount(1, $upcoming);
        $this->assertEquals($badge1->id, $upcoming[0]['badge']->id);
        $this->assertEquals(50, $upcoming[0]['progress']['percentage']);
    }

    public function test_duplicate_badges_not_awarded()
    {
        // Create test badge
        $badge = Badge::create([
            'id'               => \Illuminate\Support\Str::uuid(),
            'name'             => 'Newcomer',
            'description'      => 'Create your first post',
            'tier'             => 'bronze',
            'condition_type'   => 'posts_count',
            'condition_value'  => 1,
        ]);

        // Create user with posts
        $user = User::factory()->create();
        $user->posts()->create([
            'id'          => \Illuminate\Support\Str::uuid(),
            'category_id' => \Illuminate\Support\Str::uuid(),
            'title'       => 'Test Post',
            'body'        => 'Test body',
        ]);

        // Award badges twice
        $this->badgeService->awardBadgesForUser($user);
        $this->badgeService->awardBadgesForUser($user);

        // Assert user only has badge once
        $this->assertEquals(1, $user->badges()->where('badge_id', $badge->id)->count());
    }

    public function test_api_get_user_badges()
    {
        $user = User::factory()->create();
        
        $badge = Badge::create([
            'id'               => \Illuminate\Support\Str::uuid(),
            'name'             => 'Newcomer',
            'description'      => 'Create your first post',
            'tier'             => 'bronze',
            'condition_type'   => 'posts_count',
            'condition_value'  => 1,
        ]);

        $user->badges()->attach($badge);

        $response = $this->getJson("/api/users/{$user->id}/badges");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
    }

    public function test_api_get_all_badges()
    {
        Badge::create([
            'id'               => \Illuminate\Support\Str::uuid(),
            'name'             => 'Test Badge 1',
            'tier'             => 'bronze',
            'condition_type'   => 'posts_count',
            'condition_value'  => 1,
        ]);

        Badge::create([
            'id'               => \Illuminate\Support\Str::uuid(),
            'name'             => 'Test Badge 2',
            'tier'             => 'silver',
            'condition_type'   => 'reputation_points',
            'condition_value'  => 500,
        ]);

        $response = $this->getJson('/api/badges');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(2, 'data');
    }
}
