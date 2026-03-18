<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutomationRuleController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\ConversationMessageController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\KeywordResponseController;
use App\Http\Controllers\Api\MetaOAuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'updateMe']);
        Route::get('me/export', [AuthController::class, 'exportMe']);
        Route::delete('me', [AuthController::class, 'deleteMe']);
    });
});

Route::middleware('jwt.auth')->group(function () {
    Route::get('business/me', [BusinessController::class, 'show']);
    Route::patch('business/me', [BusinessController::class, 'update']);

    Route::middleware('can:isAdmin')->group(function () {
        Route::get('users', [UserController::class, 'index']);
    });

    Route::get('channels', [ChannelController::class, 'index']);
    Route::get('channels/{channel}', [ChannelController::class, 'show']);
    Route::put('channels/{channel}', [ChannelController::class, 'update']);
    Route::delete('channels/{channel}', [ChannelController::class, 'destroy']);

    Route::get('conversations/{conversation}/messages', [ConversationMessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [ConversationMessageController::class, 'store']);

    Route::get('feedback/conversation/{conversation}', [FeedbackController::class, 'showByConversation']);
    Route::get('feedback/coaching', [FeedbackController::class, 'coachingSummary']);
    Route::post('feedback', [FeedbackController::class, 'store']);

    Route::get('automation-rules', [AutomationRuleController::class, 'index']);
    Route::post('automation-rules', [AutomationRuleController::class, 'store']);
    Route::put('automation-rules/{automationRule}', [AutomationRuleController::class, 'update']);
    Route::delete('automation-rules/{automationRule}', [AutomationRuleController::class, 'destroy']);

    Route::get('keyword-responses', [KeywordResponseController::class, 'index']);
    Route::post('keyword-responses', [KeywordResponseController::class, 'store']);
    Route::put('keyword-responses/{keywordResponse}', [KeywordResponseController::class, 'update']);
    Route::delete('keyword-responses/{keywordResponse}', [KeywordResponseController::class, 'destroy']);
});

Route::get('oauth/meta/start', [MetaOAuthController::class, 'start']);

