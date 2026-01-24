<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ValidateUserToken;
use Illuminate\Support\Facades\Route;

// Health routes
Route::get('', [HealthController::class, 'check']);
Route::get('/health', [HealthController::class, 'check']);

// User routes
Route::prefix('user')->group(function () {
    Route::get('/all', [UserController::class, 'index']);

    Route::post('', [UserController::class, 'store']);
    Route::get('/{userUUID}', [UserController::class, 'show'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::put('/{userUUID}', [UserController::class, 'update'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::delete('/{userUUID}', [UserController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::get('/github/{githubEmail}', [UserController::class, 'findByGithubEmail']);
    Route::get('/google/{googleEmail}', [UserController::class, 'findByGoogleEmail']);
    Route::post('/resend/validationcode', [UserController::class, 'resendEmail']);
    Route::patch('/validate/github/email/{userUUID}', [UserController::class, 'validateGithubEmail'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::patch('/validate/google/email/{userUUID}', [UserController::class, 'validateGoogleEmail'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::patch('/validate/{userUUID}', [UserController::class, 'validate'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);
});

// Planning routes
Route::prefix('planning')->group(function () {
    Route::post('/search/{userUUID}', [PlanningController::class, 'searchByFilters'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::get('/paginate/{userUUID}', [PlanningController::class, 'index'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::post('', [PlanningController::class, 'store']);
    Route::get('/show/{uuid}', [PlanningController::class, 'show']);
    Route::put('/{uuid}', [PlanningController::class, 'update']);
    Route::delete('/{uuid}', [PlanningController::class, 'destroy']);
    Route::patch('/archive/{uuid}', [PlanningController::class, 'archive']);
    Route::patch('/unarchive/{uuid}', [PlanningController::class, 'unarchive']);
});

// Plans routes
Route::prefix('plans')->group(function () {
    Route::get('/', [PlansController::class, 'index']);
    Route::get('/{uuid}', [PlansController::class, 'show']);
});

// Subscription routes
Route::prefix('subscription')->group(function () {
    Route::post('/assign/free/{userUUID}', [SubscriptionController::class, 'assignFreePlanToUser'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::post('/assign/{userUUID}', [SubscriptionController::class, 'assignPlanToUser'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);


    Route::put('/{userUUID}', [SubscriptionController::class, 'assignPlanToUser'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);


    Route::patch('/status/update/{subscriptionId}', [SubscriptionController::class, 'patchPlanStatus']);
    Route::get('/{userUUID}', [SubscriptionController::class, 'show'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);
});

// Payment history routes
Route::prefix('payment/history')->group(function () {
    Route::get('/{userUUID}', [PaymentHistoryController::class, 'show'])
        ->middleware('auth:sanctum')
        ->middleware(ValidateUserToken::class);

    Route::post('/', [PaymentHistoryController::class, 'store']);
    Route::put('/{paymentId}', [PaymentHistoryController::class, 'update']);

    Route::patch('/upload/nfe/{paymentId}', [PaymentHistoryController::class, 'insertNFe']);
    Route::patch('/status/update/{paymentId}', [PaymentHistoryController::class, 'updatePaymentStatus']);
});

// Auth routes
Route::get('/token/github/{code}', [AuthController::class, 'githubAccessToken']);
Route::get('/auth/github/{token}', [AuthController::class, 'githubAuth']);
Route::get('/auth/google/{token}', [AuthController::class, 'googleAuth']);
Route::delete('/logout/{userUUID}', [AuthController::class, 'logout'])->middleware('auth:sactum');
