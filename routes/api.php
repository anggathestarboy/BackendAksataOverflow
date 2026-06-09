<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentEditHistoryController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PointsLogController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostEditHistoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\VoteController;
use App\Http\Middleware\IsAdminMiddleware;
use App\Http\Middleware\IsBannedMiddleware;
use App\Http\Middleware\IsModeratorMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::middleware("throttle:api")->group(function () {
   Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/users-all', [AuthController::class, 'getAllUser']);
Route::get('/detail-user/{username}', [AuthController::class, 'getDetailUser']);
Route::get('/likes/{username}', [LikeController::class, 'index']);
Route::get('/leaderboard', [FeatureController::class, 'leaderboard']);
Route::get("/search-users", [AuthController::class, 'searchUsers']);


Route::middleware(['auth:api', IsBannedMiddleware::class ])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post(("/follow"), [FollowController::class, 'follow']);
    Route::delete(("/unfollow"), [FollowController::class, 'unfollow']);

    Route::post("/change-password", [AuthController::class, 'resetPassword']);
    Route::apiResource("/categories", CategoryController::class)->except(['destroy', 'update', 'store']);
    Route::apiResource("/tags", TagController::class)->except(['destroy']);
    Route::apiResource("/posts", PostController::class)->except(['show']);
    Route::post('/posts/{id}/accept-answer', [PostController::class, 'acceptAnswer']);
    Route::post('/posts/{id}/close', [PostController::class, 'close']);
    Route::apiResource('/comments', CommentController::class)->except(['destroy']);
    Route::post("/likes", [LikeController::class, 'store']);
    Route::delete("/unlikes", [LikeController::class, 'unlike']);
    Route::apiResource("/bookmarks", BookmarkController::class);
    Route::post("/votes", [VoteController::class, 'vote']);
    Route::post("/downvotes", [VoteController::class, 'downVote']);
    Route::post("/reports", [ReportController::class, 'store']);
    Route::get("/reports", [ReportController::class, 'getUserReports']);
Route::get("/points-logs/{username}", [PointsLogController::class, 'byUser']);
    Route::get('moderation-logs/user', [AuthController::class, 'getModerationLogsByUser']);


    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Reputation / Points Log routes
    Route::get('/points-logs', [PointsLogController::class, 'index']);


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
        Route::get('/reports-all', [ReportController::class, 'getAllReports']);
        Route::patch('/reports-resolve/{id}', [ReportController::class, 'resolveReport']);
        Route::get('/comment-histories/{id}', [CommentEditHistoryController::class, 'index']);
        Route::get('/post-histories/{id}', [PostEditHistoryController::class, 'index']);
        Route::apiResource('/badges', BadgeController::class)->except(["update"]);
        Route::post('/badges/{id}', [BadgeController::class, 'update']);
        Route::post('/banned/{username}', [AuthController::class, 'banUser']);
       Route::post('/warnings/{username}', [AuthController::class, 'createWarning']);
       Route::get('/moderation-logs', [AuthController::class, 'getAllModerationLogs']);
    });
});

Route::get("/posts", [PostController::class, 'index']);
Route::get("/posts/{id}", [PostController::class, 'show']);
Route::get("/followers/{username}", [FollowController::class, 'followers']);
Route::get("/followings/{username}", [FollowController::class, 'following']);

Route::get("/users/{username}/level", [AuthController::class, 'getLevelInfo']);
Route::get('/badges', [BadgeController::class, 'index']);
Route::get('/users/{user}/badges', [BadgeController::class, 'getUserBadges']);
Route::get('/users/{user}/badges/upcoming', [BadgeController::class, 'getUpcomingBadges']);
Route::get('/badges/{badge}', [BadgeController::class, 'show']);


Route::middleware(['auth:api', IsAdminMiddleware::class])->group(function () {
 Route::post('/unbanned/{username}', [AuthController::class, 'unbanUser']);
});
});



