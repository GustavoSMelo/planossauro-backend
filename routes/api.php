<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Health routes
Route::get('', [HealthController::class, 'check']);
Route::get('/health', [HealthController::class, 'check']);

// User routes
Route::prefix('user')->group(function () {
    Route::get('/all', [UserController::class, 'index']);
    Route::post('', [UserController::class, 'store']);
    Route::get('/{uuid}', [UserController::class, 'show']);
    Route::put('/{uuid}', [UserController::class, 'update']);
    Route::delete('/{uuid}', [UserController::class, 'destroy']);
    Route::get('/github/{githubEmail}', [UserController::class, 'findByGithubEmail']);
    Route::get('/google/{googleEmail}', [UserController::class, 'findByGoogleEmail']);
    Route::post('/resend/validationcode', [UserController::class, 'resendEmail']);
    Route::patch('/validate/github/email/{uuid}', [UserController::class, 'validateGithubEmail']);
    Route::patch('/validate/google/email/{uuid}', [UserController::class, 'validateGoogleEmail']);
    Route::patch('/validate/{uuid}', [UserController::class, 'validate']);
});


// Planning routes
Route::prefix('planning')->group(function () {
    Route::post('/search/{uuid}', [PlanningController::class, 'searchByFilters']);
    Route::get('/paginate/{uuid}', [PlanningController::class, 'index']);
    Route::post('', [PlanningController::class, 'store']);
    Route::get('/show/{uuid}', [PlanningController::class, 'show']);
    Route::put('/{uuid}', [PlanningController::class, 'update']);
    Route::delete('/{uuid}', [PlanningController::class, 'destroy']);
    Route::patch('/archive/{uuid}', [PlanningController::class, 'archive']);
    Route::patch('/unarchive/{uuid}', [PlanningController::class, 'unarchive']);
});

// Plans routes
Route::get('/plans', [PlansController::class, 'index']);
Route::get('/plans/{uuid}', [PlansController::class, 'show']);

// Auth routes
Route::get('/auth/github/{code}', [AuthController::class, 'githubAuth']);
