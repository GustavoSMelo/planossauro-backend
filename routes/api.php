<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\UserController;
use App\Models\UserPlanning;
use Illuminate\Support\Facades\Route;

// User routes

Route::get('/user/all', [UserController::class, 'index']);
Route::post('/user', [UserController::class, 'store']);
Route::get('/user/{uuid}', [UserController::class, 'show']);
Route::put('/user/{uuid}', [UserController::class, 'update']);
Route::delete('/user/{uuid}', [UserController::class, 'destroy']);
Route::get('/user/github/{githubEmail}', [UserController::class, 'findByGithubEmail']);
Route::post('/user/resend/validationcode', [UserController::class, 'resendEmail']);
Route::patch('/user/validate/email/{uuid}', [UserController::class, 'validateEmail']);

// Planning routes

Route::get('/planning', [PlanningController::class, 'index']);
Route::post('/planning', [PlanningController::class, 'store']);
Route::get('/planning/{uuid}', [PlanningController::class, 'show']);
Route::put('/planning/{uuid}', [PlanningController::class, 'update']);
Route::delete('/planning/{uuid}', [PlanningController::class, 'destroy']);
Route::patch('/planning/archive/{uuid}', [PlanningController::class, 'archive']);
Route::patch('/planning/unarchive/{uuid}', [PlanningController::class, 'unarchive']);

// Plans routes

Route::get('/plans', [PlansController::class, 'index']);
Route::get('/plans/{uuid}', [PlansController::class, 'show']);

// Auth routes
Route::get('/auth/github/{code}', [AuthController::class, 'githubAuth']);
