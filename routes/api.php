<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\VideoStreamController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Api\Admin\MeetingController as AdminMeetingController;
use App\Http\Controllers\Api\Admin\MaterialController as AdminMaterialController;
use App\Http\Controllers\Api\Admin\PollController as AdminPollController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\MessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Public Routes
Route::get('/material/stream/{material}', [MaterialController::class, 'stream'])->name('material.stream');

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Users
    Route::apiResource('users', UserController::class);

    // News
    Route::get('/news/latest', [NewsController::class, 'latest']);
    Route::apiResource('news', NewsController::class);

    // Polls
    Route::get('/polls/active', [PollController::class, 'active']);
    Route::apiResource('polls', PollController::class);
    Route::post('polls/{poll}/respond', [PollController::class, 'respond']);

    // Votes
    Route::get('votes/active', [VoteController::class, 'active']);
    Route::get('votes/{poll}/user-responses', [VoteController::class, 'userResponses']);
    Route::get('votes/{poll}', [VoteController::class, 'show']);
    Route::post('votes/{poll}', [VoteController::class, 'store']);
    Route::get('votes/active/count', [VoteController::class, 'activeCount']);

    // Meetings
    Route::get('/meetings/upcoming', [MeetingController::class, 'upcoming']);
    Route::apiResource('meetings', MeetingController::class);

    // Chat
    Route::get('chat/messages', [ChatController::class, 'index']);
    Route::post('chat/messages', [ChatController::class, 'store']);

    // Materials
    Route::post('/materials', [MaterialController::class, 'store']);
    Route::apiResource('materials', MaterialController::class)->except(['store']);

    // Admin Routes
    Route::prefix('admin')->middleware('can:viewAny,App\Models\User')->group(function () {
        // Dashboard
        Route::get('statistics', [AdminDashboardController::class, 'statistics']);
        Route::get('recent-activities', [AdminDashboardController::class, 'recentActivities']);

        // Users
        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users', [AdminUserController::class, 'store']);
        Route::put('users/{user}', [AdminUserController::class, 'update']);
        Route::delete('users/{user}', [AdminUserController::class, 'destroy']);

        // News
        Route::get('news', [AdminNewsController::class, 'index']);
        Route::post('news', [AdminNewsController::class, 'store']);
        Route::put('news/{news}', [AdminNewsController::class, 'update']);
        Route::delete('news/{news}', [AdminNewsController::class, 'destroy']);

        // Polls
        Route::get('polls', [AdminPollController::class, 'index']);
        Route::post('polls', [AdminPollController::class, 'store']);
        Route::get('polls/{poll}', [AdminPollController::class, 'show']);
        Route::put('polls/{poll}', [AdminPollController::class, 'update']);
        Route::delete('polls/{poll}', [AdminPollController::class, 'destroy']);
        Route::get('polls/results/{poll}', [AdminPollController::class, 'results']);
        Route::patch('polls/{poll}/toggle-active', [AdminPollController::class, 'toggleActive'])->name('polls.toggle-active');

        // Meetings
        Route::get('meetings', [AdminMeetingController::class, 'index']);
        Route::post('meetings', [AdminMeetingController::class, 'store']);
        Route::put('meetings/{meeting}', [AdminMeetingController::class, 'update']);
        Route::delete('meetings/{meeting}', [AdminMeetingController::class, 'destroy']);

        // Materials
        Route::post('/materials', [AdminMaterialController::class, 'store']);
        Route::get('/materials', [AdminMaterialController::class, 'index']);
        Route::delete('/materials/{material}', [AdminMaterialController::class, 'destroy']);
    });

    // Messages
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
});