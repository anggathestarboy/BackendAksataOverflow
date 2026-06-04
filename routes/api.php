<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
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
    Route::apiResource("/comments", CommentController::class);
});

Route::get("/followers/{username}", [FollowController::class, 'followers']);
Route::get("/followings/{username}", [FollowController::class, 'following']);
