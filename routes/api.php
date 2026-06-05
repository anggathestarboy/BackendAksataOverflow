<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use App\Http\Middleware\IsAdminMiddleware;
use App\Http\Middleware\IsModeratorMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post(("/follow"), [FollowController::class, 'follow']);
    Route::delete(("/unfollow"), [FollowController::class, 'unfollow']);

    Route::apiResource("/categories", CategoryController::class);
    Route::apiResource("/tags", TagController::class);
    Route::apiResource("/posts", PostController::class);
    Route::apiResource('/comments', CommentController::class)->except(['destroy']);
    Route::post("/likes", [LikeController::class, 'store']);
    Route::delete("/unlikes", [LikeController::class, 'unlike']);
    Route::apiResource("/bookmarks", BookmarkController::class);

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
    });
});

Route::get("/posts", [PostController::class, 'index']);
Route::get("/followers/{username}", [FollowController::class, 'followers']);
Route::get("/followings/{username}", [FollowController::class, 'following']);
