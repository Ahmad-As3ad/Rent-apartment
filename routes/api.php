<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('send-otp', [AuthController::class, 'sendOTP']);
Route::post('verify-otp', [AuthController::class, 'verifyOTP']);

    Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('profile', [ProfileController::class, 'getProfile']);
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);

    Route::middleware('user.status')->group(function () {
        // coming soon
    });
});
