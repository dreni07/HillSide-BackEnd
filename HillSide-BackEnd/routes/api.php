<?php

use App\Http\Controllers\Api\AiConfigController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutomationRuleController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\BusinessTypeController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ConversationMessageController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\KeywordResponseController;
use App\Http\Controllers\Api\MetaOAuthController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
});

Route::controller(MetaOAuthController::class)->prefix('oauth')->group(function () {
    Route::get('meta/start', 'start');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('jwt.auth')->group(function () {

    Route::controller(AuthController::class)->prefix('auth')->group(function () {
        Route::get('me', 'me');
        Route::patch('me', 'updateMe');
        Route::get('me/export', 'exportMe');
        Route::delete('me', 'deleteMe');
    });

    Route::controller(BusinessTypeController::class)->group(function () {
        Route::get('business-types', 'index');
    });

    Route::controller(BusinessController::class)->group(function () {
        Route::get('business/me', 'show');
        Route::post('businesses', 'store');
        Route::put('businesses/{business}', 'update');
        Route::put('businesses/{business}/business-type', 'assignBusinessType');
    });

    Route::controller(AiConfigController::class)->prefix('businesses/{business}')->group(function () {
        Route::post('ai-config', 'store');
        Route::get('ai-config', 'show');
    });

    Route::controller(ChannelController::class)->prefix('channels')->group(function () {
        Route::get('/', 'index');
        Route::get('{channel}', 'show');
        Route::put('{channel}', 'update');
        Route::delete('{channel}', 'destroy');
    });

    Route::controller(ContactController::class)->prefix('contacts')->group(function () {
        Route::get('/', 'index');
        Route::get('{contact}', 'show');
        Route::put('{contact}', 'update');
    });

    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);

        Route::controller(ConversationMessageController::class)->prefix('{conversation}/messages')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
        });
    });

    Route::controller(FeedbackController::class)->prefix('feedback')->group(function () {
        Route::get('conversation/{conversation}', 'showByConversation');
        Route::get('coaching', 'coachingSummary');
        Route::get('overview', 'overview');
        Route::post('/', 'store');
    });

    Route::controller(StatsController::class)->prefix('stats')->group(function () {
        Route::get('overview', 'overview');
    });

    Route::controller(AutomationRuleController::class)->prefix('automation-rules')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::put('{automationRule}', 'update');
        Route::delete('{automationRule}', 'destroy');
    });

    Route::controller(KeywordResponseController::class)->prefix('keyword-responses')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::put('{keywordResponse}', 'update');
        Route::delete('{keywordResponse}', 'destroy');
    });

    Route::middleware('can:isAdmin')->group(function () {
        Route::controller(UserController::class)->group(function () {
            Route::get('users', 'index');
        });
    });
});
