<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\VoteController;
use App\Http\Middleware\IsAdminMiddleware;
use App\Http\Middleware\IsModeratorMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/users-all', [AuthController::class, 'getAllUser']);



Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post(("/follow"), [FollowController::class, 'follow']);
    Route::delete(("/unfollow"), [FollowController::class, 'unfollow']);

    Route::apiResource("/categories", CategoryController::class)->except(['destroy', 'update', 'store']);
    Route::apiResource("/tags", TagController::class)->except(['destroy']);
    Route::apiResource("/posts", PostController::class)->except(['show']);
    Route::post('/posts/{id}/accept-answer', [PostController::class, 'acceptAnswer']);
    Route::apiResource('/comments', CommentController::class)->except(['destroy']);
    Route::post("/likes", [LikeController::class, 'store']);
    Route::delete("/unlikes", [LikeController::class, 'unlike']);
    Route::apiResource("/bookmarks", BookmarkController::class);
    Route::post("/votes", [VoteController::class, 'vote']);
    Route::post("/downvotes", [VoteController::class, 'downVote']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);


    Route::middleware(IsAdminMiddleware::class)->group(function () {
        Route::prefix('/admin')->group(function () {
            Route::post('/promote-moderator/{username}', [AuthController::class, 'jadikanModerator']);
            Route::post('/promote-admin/{username}', [AuthController::class, 'jadikanAdmin']);
            Route::post('/turunkan/{username}', [AuthController::class, 'turunkanJabatan']);
            
        });
    });


    Route::middleware([IsModeratorMiddleware::class])->group(function () {
            Route::delete('/posts/{id}', [PostController::class, 'destroy']);
            Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
            Route::delete('/tags/{id}', [TagController::class, 'destroy']);
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{slug}', [CategoryController::class, 'update']);
           Route::delete('categories/{slug}', [CategoryController::class, 'destroy']);
            
            
    });
});

Route::get("/posts", [PostController::class, 'index']);
Route::get("/posts/{id}", [PostController::class, 'show']);
Route::get("/followers/{username}", [FollowController::class, 'followers']);
Route::get("/followings/{username}", [FollowController::class, 'following']);
