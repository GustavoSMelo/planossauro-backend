<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// User routes

Route::get('/user/all', [UserController::class, 'index']);
Route::post('/user', [UserController::class, 'store']);
Route::get('/user/{uuid}', [UserController::class, 'show']);
Route::put('/user/{uuid}', [UserController::class, 'update']);
Route::delete('/user/{uuid}', [UserController::class, 'destroy']);

// Planning routes

Route::get('/planning', [PlanningController::class, 'index']);
Route::post('/planning', [PlanningController::class, 'store']);
Route::get('/planning/{uuid}', [PlanningController::class, 'show']);
Route::put('/planning/{uuid}', [PlanningController::class, 'update']);
Route::delete('/planning/{uuid}', [PlanningController::class, 'destroy']);

// Plans routes

Route::get('/plans', [PlansController::class, 'index']);
Route::get('/plans/{uuid}', [PlansController::class, 'show']);

// Auth routes
Route::get('/auth/github/{code}', [AuthController::class, 'githubAuth']);
