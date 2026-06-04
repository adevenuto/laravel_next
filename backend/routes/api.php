<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Public auth routes — strict throttle to slow credential-stuffing
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password-reset', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/password-reset/confirm', [PasswordResetController::class, 'confirm']);
});

// Protected routes (Sanctum: SPA cookie or Bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
