<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ExerciseAttemptController;
use App\Http\Controllers\LessonCompleteController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LevelMapController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\ProfileController;
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

    // App profile endpoint — richer than /api/user (xp, vocab counter, streak, badges,
    // feature flags).
    Route::get('/me', [MeController::class, 'show']);

    // Gameplay — server-side unlock state is enforced inside each controller via
    // ProgressService::unlockState; clients never decide what's playable.
    Route::get('/levels/{level:number}/map', [LevelMapController::class, 'show']);
    Route::get('/lessons/{lesson:slug}', [LessonController::class, 'show']);
    Route::post('/exercises/{exercise}/attempt', [ExerciseAttemptController::class, 'store']);
    Route::post('/lessons/{lesson:slug}/complete', [LessonCompleteController::class, 'store']);

    // Profile — used by both the first-time onboarding modal (Phase 4) and the
    // profile edit page. Sets display_name + hometown which feed SlotFill and
    // boss dialogue interpolation.
    Route::post('/profile/onboarding', [ProfileController::class, 'onboarding']);
});
