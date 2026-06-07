<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Bookmark;
use App\Models\Vote;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_get_detail_user_returns_correct_post_counts_and_interactions()
    {
        // 1. Create target user whose detail we will fetch
        $targetUser = User::create([
            'username' => 'target_user',
            'email' => 'target@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        // 2. Create another user who will interact
        $viewerUser = User::create([
            'username' => 'viewer_user',
            'email' => 'viewer@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        // 3. Create a category and posts for target user
        $category = Category::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'General',
            'slug' => 'general',
        ]);

        $post1 = Post::create([
            'user_id' => $targetUser->id,
            'category_id' => $category->id,
            'title' => 'Post One',
            'body' => 'This is post one content',
            'status' => 'open',
        ]);

        $post2 = Post::create([
            'user_id' => $targetUser->id,
            'category_id' => $category->id,
            'title' => 'Post Two (Deleted)',
            'body' => 'This is deleted post two content',
            'status' => 'deleted',
        ]);

        // 4. Create likes, comments, bookmarks, and votes for post1
        Like::create([
            'user_id' => $viewerUser->id,
            'target_id' => $post1->id,
            'target_type' => 'post',
        ]);

        Comment::create([
            'user_id' => $viewerUser->id,
            'post_id' => $post1->id,
            'body' => 'Great post!',
        ]);

        Bookmark::create([
            'user_id' => $viewerUser->id,
            'post_id' => $post1->id,
        ]);

        Vote::create([
            'user_id' => $viewerUser->id,
            'target_id' => $post1->id,
            'target_type' => 'post',
            'vote_type' => 'upvote',
        ]);

        // 5. Test detail-user endpoint when NOT logged in (guest)
        $response = $this->getJson("/api/detail-user/{$targetUser->username}");
        $response->assertStatus(200)
            ->assertJsonPath('message', 'success get user detail')
            ->assertJsonPath('user.username', 'target_user')
            // Count of posts should only be non-deleted posts
            ->assertJsonPath('user.posts_count', 1);

        $posts = $response->json('user.posts');
        $this->assertCount(1, $posts);
        $this->assertEquals('Post One', $posts[0]['title']);
        $this->assertEquals(1, $posts[0]['likes_count']);
        $this->assertEquals(1, $posts[0]['bookmarks_count']);
        $this->assertEquals(1, $posts[0]['comments_count']);
        $this->assertEquals(1, $posts[0]['votes_count']);
        $this->assertFalse($posts[0]['user_has_liked']);
        $this->assertFalse($posts[0]['user_has_bookmarked']);

        // 6. Test detail-user endpoint when logged in as viewerUser
        $response = $this->actingAs($viewerUser, 'api')
            ->getJson("/api/detail-user/{$targetUser->username}");

        $response->assertStatus(200);
        $posts = $response->json('user.posts');
        $this->assertCount(1, $posts);
        $this->assertTrue($posts[0]['user_has_liked']);
        $this->assertTrue($posts[0]['user_has_bookmarked']);
    }
}
