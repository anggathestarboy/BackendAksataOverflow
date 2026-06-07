<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use App\Models\Comment;
use App\Models\CommentEditHistory;
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

    public function test_post_detail_comments_formatting_and_interaction_fields()
    {
        // 1. Create a user
        $user = User::create([
            'username' => 'test_user',
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $viewer = User::create([
            'username' => 'viewer_user',
            'email' => 'viewer@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        // 2. Create Category and Post
        $category = Category::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Sample Post',
            'body' => 'Post body content',
            'status' => 'open',
        ]);

        // 3. Create parent comment
        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'body' => 'Parent comment',
        ]);

        // 4. Create reply
        $reply = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => $comment->id,
            'body' => 'Reply comment',
        ]);

        // 5. Create Like and Vote on comment
        Like::create([
            'user_id' => $viewer->id,
            'target_id' => $comment->id,
            'target_type' => 'comment',
        ]);

        Vote::create([
            'user_id' => $viewer->id,
            'target_id' => $comment->id,
            'target_type' => 'comment',
            'vote_type' => 'upvote',
        ]);

        // Create Vote on reply
        Vote::create([
            'user_id' => $viewer->id,
            'target_id' => $reply->id,
            'target_type' => 'comment',
            'vote_type' => 'downvote',
        ]);

        // Create edit history for parent comment
        CommentEditHistory::create([
            'comment_id' => $comment->id,
            'edited_by' => $user->id,
            'body_before' => 'Parent comment before edit',
            'body_after' => 'Parent comment',
            'edited_at' => now(),
        ]);

        // 6. Test public post detail response (guest)
        $response = $this->getJson("/api/posts/{$post->id}");
        $response->assertStatus(200);

        $comments = $response->json('data.comments');
        // Find the parent comment
        $parentCommentJson = collect($comments)->firstWhere('id', $comment->id);
        
        $this->assertNotNull($parentCommentJson);
        $this->assertEquals(1, $parentCommentJson['comments_count']);
        $this->assertEquals(1, $parentCommentJson['likes_count']);
        $this->assertEquals(1, $parentCommentJson['upvotes_count']);
        $this->assertEquals(0, $parentCommentJson['downvotes_count']);
        $this->assertEquals(0, $parentCommentJson['comments_upvotes_count']);
        $this->assertEquals(1, $parentCommentJson['comments_downvotes_count']); // reply has 1 downvote
        $this->assertTrue($parentCommentJson['is_edited']); // Should be true
        $this->assertFalse($parentCommentJson['user_has_voted']);
        $this->assertNull($parentCommentJson['user_vote_type']);
        $this->assertFalse($parentCommentJson['user_has_liked']);

        // Check the reply nested inside replies list
        $replyCommentJson = collect($parentCommentJson['replies'])->firstWhere('id', $reply->id);
        $this->assertNotNull($replyCommentJson);
        $this->assertEquals(0, $replyCommentJson['comments_count']);
        $this->assertEquals(0, $replyCommentJson['likes_count']);
        $this->assertEquals(0, $replyCommentJson['upvotes_count']);
        $this->assertEquals(1, $replyCommentJson['downvotes_count']);
        $this->assertFalse($replyCommentJson['is_edited']); // Should be false
        $this->assertFalse($replyCommentJson['user_has_voted']);
        $this->assertNull($replyCommentJson['user_vote_type']);
        $this->assertFalse($replyCommentJson['user_has_liked']);

        // 7. Test post detail when logged in as viewer
        $response = $this->actingAs($viewer, 'api')
            ->getJson("/api/posts/{$post->id}");
        $response->assertStatus(200);

        $comments = $response->json('data.comments');
        $parentCommentJson = collect($comments)->firstWhere('id', $comment->id);
        $this->assertTrue($parentCommentJson['user_has_voted']);
        $this->assertEquals('upvote', $parentCommentJson['user_vote_type']);
        $this->assertTrue($parentCommentJson['user_has_liked']);
        $this->assertTrue($parentCommentJson['is_edited']);

        $replyCommentJson = collect($parentCommentJson['replies'])->firstWhere('id', $reply->id);
        $this->assertTrue($replyCommentJson['user_has_voted']);
        $this->assertEquals('downvote', $replyCommentJson['user_vote_type']);
        $this->assertFalse($replyCommentJson['user_has_liked']);
        $this->assertFalse($replyCommentJson['is_edited']);

        // 8. Test GET /api/comments index endpoint
        $commentsIndexRes = $this->actingAs($viewer, 'api')
            ->getJson("/api/comments");
        $commentsIndexRes->assertStatus(200);
        $commentsData = $commentsIndexRes->json('data');
        
        $parentCommentIndex = collect($commentsData)->firstWhere('id', $comment->id);
        $this->assertNotNull($parentCommentIndex);
        $this->assertTrue($parentCommentIndex['is_edited']);

        $replyCommentIndex = collect($parentCommentIndex['replies'])->firstWhere('id', $reply->id);
        $this->assertNotNull($replyCommentIndex);
        $this->assertFalse($replyCommentIndex['is_edited']);
    }

    public function test_post_edit_histories_endpoint()
    {
        // 1. Create a moderator user
        $moderator = User::create([
            'username' => 'mod_user',
            'email' => 'mod@example.com',
            'password_hash' => bcrypt('password'),
        ]);
        $moderatorRole = \App\Models\Role::where('name', 'moderator')->first();
        \App\Models\UserRole::create([
            'user_id' => $moderator->id,
            'role_id' => $moderatorRole->id,
        ]);

        // 2. Create another user who will author/edit post
        $author = User::create([
            'username' => 'author_user',
            'email' => 'author@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        // 3. Create post
        $category = Category::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'General 2',
            'slug' => 'general-2',
        ]);
        $post = Post::create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Sample Post Title',
            'body' => 'Sample Post Body',
            'status' => 'open',
        ]);

        // 4. Create post edit history record
        \App\Models\PostEditHistory::create([
            'post_id' => $post->id,
            'edited_by' => $author->id,
            'reason' => 'Fix typos',
            'body_before' => 'Sample Post Body with typoos',
            'body_after' => 'Sample Post Body',
        ]);

        // 5. Query the endpoint as moderator
        $response = $this->actingAs($moderator, 'api')
            ->getJson("/api/post-histories/{$post->id}");

        $response->assertStatus(200);
        $histories = $response->json('histories');
        $this->assertCount(1, $histories);
        $this->assertEquals('Fix typos', $histories[0]['reason']);
        $this->assertEquals('author_user', $histories[0]['user']['username']);
        $this->assertEquals('Sample Post Title', $histories[0]['post']['title']);

        // Create an unedited post
        $uneditedPost = Post::create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Unedited Post Title',
            'body' => 'Unedited Post Body',
            'status' => 'open',
        ]);

        // Test show endpoint for edited post (is_edited => true)
        $showEditedRes = $this->getJson("/api/posts/{$post->id}");
        $showEditedRes->assertStatus(200);
        $this->assertTrue($showEditedRes->json('data.is_edited'));

        // Test show endpoint for unedited post (is_edited => false)
        $showUneditedRes = $this->getJson("/api/posts/{$uneditedPost->id}");
        $showUneditedRes->assertStatus(200);
        $this->assertFalse($showUneditedRes->json('data.is_edited'));

        // Test index endpoint
        $indexRes = $this->getJson("/api/posts");
        $indexRes->assertStatus(200);
        
        $postsData = $indexRes->json('data');
        $postFromIndex = collect($postsData)->firstWhere('id', $post->id);
        $uneditedPostFromIndex = collect($postsData)->firstWhere('id', $uneditedPost->id);

        $this->assertNotNull($postFromIndex);
        $this->assertTrue($postFromIndex['is_edited']);

        $this->assertNotNull($uneditedPostFromIndex);
        $this->assertFalse($uneditedPostFromIndex['is_edited']);
    }
}

