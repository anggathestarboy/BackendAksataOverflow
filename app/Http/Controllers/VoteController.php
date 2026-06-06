<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function vote(Request $request)
    {
        $request->validate([
            'target_id' => 'required|uuid',
        ]);

        $user = Auth::user();
        $targetId = $request->target_id;

        // Cari target (post atau comment)
        $post = Post::where("id", $targetId)->first();
        $comment = Comment::where("id", $targetId)->first();

        if (!$post && !$comment) {
            return response()->json(["message" => "Target not found"], 404);
        }

        $checkPost = $post ?? ($comment ? Post::find($comment->post_id) : null);
        if ($checkPost && $checkPost->status === 'deleted') {
            return response()->json(["message" => "Cannot vote on a deleted post"], 403);
        }

        $targetType = $post ? "post" : "comment";
        $target = $post ?? $comment;

        // Gunakan transaction untuk menjaga konsistensi data
        DB::beginTransaction();

        try {
            // Cari vote yang sudah ada dari user untuk target ini
            $existingVote = Vote::where('user_id', $user->id)
                ->where('target_id', $targetId)
                ->first();

            // Jika sudah ada vote
            if ($existingVote) {
                // Jika sudah upvote, hapus vote (toggle off)
                if ($existingVote->vote_type === 'upvote') {
                    $existingVote->delete();
                    
                    // Kurangi vote_score pada post (jika target adalah post)
                    if ($targetType === 'post') {
                        $post->decrement('vote_score');
                    }
                    
                    DB::commit();
                    
                    return response()->json([
                        "message" => ucfirst($targetType) . " vote removed successfully",
                        "action" => "removed",
                        "vote_score" => $targetType === 'post' ? $post->fresh()->vote_score : null
                    ]);
                }
                // Jika downvote, update ke upvote
                else {
                    $existingVote->update([
                        'vote_type' => 'upvote'
                    ]);
                    
                    // Update vote_score: dari downvote (-1) ke upvote (+1) = naik 2 poin
                    if ($targetType === 'post') {
                        $post->increment('vote_score', 2);
                    }
                    
                    DB::commit();
                    
                    return response()->json([
                        "message" => ucfirst($targetType) . " changed from downvote to upvote successfully",
                        "data" => $existingVote,
                        "action" => "changed_to_upvote",
                        "vote_score" => $targetType === 'post' ? $post->fresh()->vote_score : null
                    ]);
                }
            }

            // Jika belum ada vote, buat upvote baru
            $vote = Vote::create([
                'user_id' => $user->id,
                'target_id' => $target->id,
                "target_type" => $targetType,
                'vote_type' => "upvote",
            ]);
            
            // Tambah vote_score pada post (jika target adalah post)
            if ($targetType === 'post') {
                $post->increment('vote_score');
            }

            // Kirim notifikasi upvote ke pemilik konten (jika bukan diri sendiri)
            $ownerId = $targetType === 'post' ? $post->user_id : $comment->user_id;
            if ($ownerId !== $user->id) {
                Notification::create([
                    'user_id' => $ownerId,
                    'actor_id' => $user->id,
                    'type' => 'upvote',
                    'reference_id' => $target->id,
                    'reference_type' => $targetType,
                ]);
            }
            
            DB::commit();

            return response()->json([
                "message" => ucfirst($targetType) . " upvoted successfully",
                "data" => $vote,
                "action" => "upvoted",
                "vote_score" => $targetType === 'post' ? $post->fresh()->vote_score : null
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Failed to process vote",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function downVote(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'target_id' => 'required|uuid',
        ]);

        $targetId = $request->target_id;

        // Cari target (post atau comment)
        $post = Post::where("id", $targetId)->first();
        $comment = Comment::where("id", $targetId)->first();

        if (!$post && !$comment) {
            return response()->json(["message" => "Target not found"], 404);
        }

        $checkPost = $post ?? ($comment ? Post::find($comment->post_id) : null);
        if ($checkPost && $checkPost->status === 'deleted') {
            return response()->json(["message" => "Cannot vote on a deleted post"], 403);
        }

        $targetType = $post ? "post" : "comment";
        $target = $post ?? $comment;

        // Gunakan transaction untuk menjaga konsistensi data
        DB::beginTransaction();

        try {
            // Cari vote yang sudah ada dari user untuk target ini
            $existingVote = Vote::where('user_id', $user->id)
                ->where('target_id', $targetId)
                ->first();

            // Jika sudah ada vote
            if ($existingVote) {
                // Jika sudah downvote, hapus vote (toggle off)
                if ($existingVote->vote_type === 'downvote') {
                    $existingVote->delete();
                    
                    // Tambah vote_score (karena menghapus downvote berarti +1)
                    if ($targetType === 'post') {
                        $post->increment('vote_score');
                    }
                    
                    DB::commit();
                    
                    return response()->json([
                        "message" => ucfirst($targetType) . " vote removed successfully",
                        "action" => "removed",
                        "vote_score" => $targetType === 'post' ? $post->fresh()->vote_score : null
                    ]);
                }
                // Jika upvote, update ke downvote
                else {
                    $existingVote->update([
                        'vote_type' => 'downvote'
                    ]);
                    
                    // Update vote_score: dari upvote (+1) ke downvote (-1) = turun 2 poin
                    if ($targetType === 'post') {
                        $post->decrement('vote_score', 2);
                    }
                    
                    DB::commit();
                    
                    return response()->json([
                        "message" => ucfirst($targetType) . " changed from upvote to downvote successfully",
                        "data" => $existingVote,
                        "action" => "changed_to_downvote",
                        "vote_score" => $targetType === 'post' ? $post->fresh()->vote_score : null
                    ]);
                }
            }

            // Jika belum ada vote, buat downvote baru
            $vote = Vote::create([
                'user_id' => $user->id,
                'target_id' => $target->id,
                "target_type" => $targetType,
                'vote_type' => "downvote",
            ]);
            
            // Kurangi vote_score pada post (jika target adalah post)
            if ($targetType === 'post') {
                $post->decrement('vote_score');
            }
            
            DB::commit();

            return response()->json([
                "message" => ucfirst($targetType) . " downvoted successfully",
                "data" => $vote,
                "action" => "downvoted",
                "vote_score" => $targetType === 'post' ? $post->fresh()->vote_score : null
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Failed to process vote",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}