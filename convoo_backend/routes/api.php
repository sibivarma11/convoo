<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);

    // Rooms
    Route::apiResource('rooms', RoomController::class);
    Route::post('/rooms/{room}/join', [RoomController::class, 'join']);
    Route::post('/rooms/{room}/leave', [RoomController::class, 'leave']);
    Route::post('/rooms/{room}/invite', [RoomController::class, 'invite']);
    Route::delete('/rooms/{room}/kick/{user}', [RoomController::class, 'kick']);
    Route::get('/rooms/{room}/members', [RoomController::class, 'members']);

    // Messages
    Route::get('/rooms/{room}/messages', [MessageController::class, 'index']);
    Route::post('/rooms/{room}/messages', [MessageController::class, 'store']);
    Route::put('/rooms/{room}/messages/{message}', [MessageController::class, 'update']);
    Route::delete('/rooms/{room}/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/rooms/{room}/messages/{message}/react', [MessageController::class, 'react']);
    Route::post('/rooms/{room}/typing', [MessageController::class, 'typing']);
    Route::post('/rooms/{room}/read', [MessageController::class, 'markAsRead']);

    // Media
    Route::post('/rooms/{room}/media', [MediaController::class, 'upload']);
});

Route::post('/media/presign', [MediaController::class, 'presign'])->middleware('auth:sanctum');
