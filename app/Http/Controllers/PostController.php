<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostEditHistory;
use App\Models\PostTag;
use App\Models\Tag;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\MentionService;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
{
    $sortBy = $request->get('sort_by', 'created_at');
    $sortOrder = $request->get('sort_order', 'desc');

    $allowedSortFields = [
        'created_at', 'title', 'updated_at',
        'votes_count', 'likes_count', 'bookmarks_count', 'comments_count'
    ];

    // Validasi sort field
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'created_at';
    }

    //  Bangun query sekali saja
    $query = Post::with(['tags', 'category', 'user'])
        ->withCount([
            'likes',
            'bookmarks',
            'comments',
            'votes as upvotes_count' => fn($q) => $q->where('vote_type', 'upvote'),
            'votes as downvotes_count' => fn($q) => $q->where('vote_type', 'downvote'),
        ]);

    // Search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('body', 'LIKE', "%{$search}%")
              ->orWhere('id', 'LIKE', "%{$search}%");
        });
    }

    // Filter category slug (single)
    if ($request->filled('category_slug')) {
        $query->whereHas('category', fn($q) => $q->where('slug', $request->category_slug));
    }

    // Filter category slugs (multiple)
    if ($request->has('category_slugs') && is_array($request->category_slugs)) {
        $query->whereHas('category', fn($q) => $q->whereIn('slug', $request->category_slugs));
    }

    //  Sort di DB kalau bukan votes_count
    if ($sortBy !== 'votes_count') {
        $query->orderBy($sortBy, $sortOrder);
    }

    $posts = $query->paginate($request->get('per_page', 10));

    // Transform SELALU dijalankan, apapun sort-nya
    $user = auth()->user();
    $posts->getCollection()->transform(function($post) use ($user) {
        $post->votes_count = $post->upvotes_count - $post->downvotes_count;
        $post->user_has_liked = $user
            ? $post->likes()->where('user_id', $user->id)->exists()
            : false;
        $post->user_has_bookmarked = $user
            ? $post->bookmarks()->where('user_id', $user->id)->exists()
            : false;
        return $post;
    });

    //  Sort manual hanya untuk votes_count (setelah transform)
    if ($sortBy === 'votes_count') {
        $sorted = $sortOrder === 'asc'
            ? $posts->getCollection()->sortBy('votes_count')
            : $posts->getCollection()->sortByDesc('votes_count');
        $posts->setCollection($sorted->values());
    }

    return response()->json($posts);
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
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category_slug' => 'required|exists:categories,slug',
            'tags' => 'array',
            'tags.*' => 'exists:tags,slug',
        ]);

        $category = Category::where('slug', $request->category_slug)->first();



        $post = Post::create([
            'user_id' => auth()->id(),
            'category_id' => $category->id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        foreach ($request->tags as $tagSlug) {
            $tag = Tag::where('slug', $tagSlug)->first();


            if ($tag) {
                PostTag::create([
                    'post_id' => $post->id,
                    'tag_id' => $tag->id,
                ]);
                $tag->increment('usage_count');
            }
        }

        // Handle @mentions in post body
        MentionService::handleMentions($request->body, auth()->id(), 'mention', $post->id, 'post');

        return response()->json([
            "message" => "post created successfully",
            "data" => $post->load('tags', 'category')
        ]);
    }

    /**
     * Display the specified resource.
     */
 public function show($id)
{
    $post = Post::with([
        'tags', 
        'category', 
        'user',
        'comments' => function($q) {
            $q->with(['user', 'votes', 'replies'])->orderBy('created_at', 'desc');
        },
        'comments.user',
        'comments.votes',
        'comments.replies.user',
        'comments.replies.votes'
    ])
    ->withCount([
        'likes', 
        'bookmarks', 
        'comments',
        'votes as upvotes_count' => function($q) {
            $q->where('vote_type', 'upvote');
        },
        'votes as downvotes_count' => function($q) {
            $q->where('vote_type', 'downvote');
        },
        // Hitung juga untuk comments
        'comments as comments_upvotes_count' => function($q) {
            $q->whereHas('votes', function($sub) {
                $sub->where('vote_type', 'upvote');
            });
        },
        'comments as comments_downvotes_count' => function($q) {
            $q->whereHas('votes', function($sub) {
                $sub->where('vote_type', 'downvote');
            });
        }
    ])
    ->findOrFail($id);
    
    // Increment view count only for logged-in users who are not the post owner
    if (auth()->check() && $post->user_id !== auth()->id()) {
        $post->increment('view_count');
    }
    
    
    // Format comments dengan vote counts
    $post->comments->transform(function($comment) {
        $comment->votes_count = $comment->votes->sum(function($vote) {
            return $vote->vote_type === 'upvote' ? 1 : -1;
        });
        
        // Format replies jika ada
        if ($comment->replies) {
            $comment->replies->transform(function($reply) {
                $reply->votes_count = $reply->votes->sum(function($vote) {
                    return $vote->vote_type === 'upvote' ? 1 : -1;
                });
                return $reply;
            });
        }
        
        return $comment;
    });
    
    // Cek interaksi user saat ini (jika login)
    if (auth()->check()) {
        $user = auth()->user();
        
        // Status vote user pada post
        $userPostVote = Vote::where('user_id', $user->id)
            ->where('target_id', $post->id)
            ->where('target_type', 'post')
            ->first();
        $post->user_has_voted = $userPostVote ? true : false;
        $post->user_vote_type = $userPostVote ? $userPostVote->vote_type : null;
        
        // Status like user pada post
        $post->user_has_liked = $post->likes()->where('user_id', $user->id)->exists();
        
        // Status bookmark user pada post
        $post->user_has_bookmarked = $post->bookmarks()->where('user_id', $user->id)->exists();
        
        // Status vote user pada setiap comment
        foreach ($post->comments as $comment) {
            $userCommentVote = Vote::where('user_id', $user->id)
                ->where('target_id', $comment->id)
                ->where('target_type', 'comment')
                ->first();
            $comment->user_has_voted = $userCommentVote ? true : false;
            $comment->user_vote_type = $userCommentVote ? $userCommentVote->vote_type : null;
            
            // Untuk replies
            if ($comment->replies) {
                foreach ($comment->replies as $reply) {
                    $userReplyVote = Vote::where('user_id', $user->id)
                        ->where('target_id', $reply->id)
                        ->where('target_type', 'comment')
                        ->first();
                    $reply->user_has_voted = $userReplyVote ? true : false;
                    $reply->user_vote_type = $userReplyVote ? $userReplyVote->vote_type : null;
                }
            }
        }
    }
    
    return response()->json([
        'status' => 'success',
        'data' => $post
    ]);
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $user = Auth::user();



        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            "reason" => 'required|string',
            'category_slug' => 'required|exists:categories,slug',
            'tags' => 'array',
            'tags.*' => 'exists:tags,slug',
        ]);

        $post = Post::where('id', $id)->first();
        $bodyBefore = $post?->body;

        if (!$post) {
            return response()->json(['message' => 'post not found'], 404);
        }

        $isOwner = $post->user_id === $user->id;
        $isAdmin = $user->roles()->where('name', 'admin')->exists();

        if (!$isOwner && !$isAdmin) {
            return response()->json(['message' => 'you cannot update this post'], 403);
        }


        $editCount = PostEditHistory::where('post_id', $post->id)->count();

        if ($editCount >= 3) {
            return response()->json([
                'message' => 'This post has reached the maximum edit limit (3 times)'
            ], 403);
        }

        $category = Category::where('slug', $request->category_slug)->first();

        $post->update([
            'category_id' => $category->id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        // hapus semua tag lama
        PostTag::where('post_id', $post->id)->delete();

        // tambahkan tag baru
        foreach ($request->tags ?? [] as $tagSlug) {
            $tag = Tag::where('slug', $tagSlug)->first();

            if ($tag) {
                PostTag::create([
                    'post_id' => $post->id,
                    'tag_id' => $tag->id,
                ]);
            }
        }

        $postEditHistory = PostEditHistory::create([
            'post_id' => $post->id,
            'edited_by' => $user->id,
            'reason' => $request->reason,
            "body_before" => $bodyBefore,
            "body_after" => $post->body,
        ]);

        // Handle @mentions in updated post body
        MentionService::handleMentions($request->body, $user->id, 'mention', $post->id, 'post');

        return response()->json([
            'message' => 'post updated successfully',
            'data' => $post->fresh()->load('tags', 'category'),
            "edit_history" => $postEditHistory
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();



        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found'
            ], 404);
        }

        $isOwner = $post->user_id === $user->id;
        $isAdmin = $user->roles()->where('name', 'admin')->exists();
        $isModerator = $user->roles()->where('name', 'moderator')->exists();

        if (!$isOwner && !$isAdmin && !$isModerator) {
            return response()->json([
                'message' => 'You do not have permission to delete this post'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully'
        ]);
    }

    /**
     * Accept a comment as the answer for a post.
     * Only the post owner can accept an answer.
     * Once accepted, the post status becomes 'closed' and no new comments are allowed.
     */
    public function acceptAnswer(Request $request, string $id)
    {
        $user = Auth::user();

        $request->validate([
            'comment_id' => 'required|exists:comments,id',
        ]);

        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Only the post owner can accept an answer
        if ($post->user_id !== $user->id) {
            return response()->json([
                'message' => 'Only the post owner can accept an answer'
            ], 403);
        }

        // Check if post already has an accepted answer
        if ($post->is_answered) {
            return response()->json([
                'message' => 'This post already has an accepted answer'
            ], 400);
        }

        // Validate the comment belongs to this post
        $comment = Comment::where('id', $request->comment_id)
            ->where('post_id', $post->id)
            ->first();

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found on this post'
            ], 404);
        }

        // Cannot accept your own comment as answer
        if ($comment->user_id === $user->id) {
            return response()->json([
                'message' => 'You cannot accept your own comment as an answer'
            ], 400);
        }

        // Accept the answer and close the post
        $post->update([
            'accepted_answer_id' => $comment->id,
            'is_answered' => true,
            'status' => 'closed',
        ]);

        // Update comment is_accepted status
        $comment->update([
            'is_accepted' => true,
        ]);

        // Notify the comment owner that their answer was accepted
        Notification::create([
            'user_id' => $comment->user_id,
            'actor_id' => $user->id,
            'type' => 'answer_accepted',
            'reference_id' => $comment->id,
            'reference_type' => 'comment',
        ]);

        return response()->json([
            'message' => 'Answer accepted successfully. Post is now closed.',
            'data' => $post->fresh()->load('tags', 'category', 'user'),
        ]);
    }
}
