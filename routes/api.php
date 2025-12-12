<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

// مسارات المصادقة العامة
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// مسارات تتطلب مصادقة
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    Route::post('profile/update', [ProfileController::class, 'updateProfile']);

    // مسارات تتطلب موافقة الإدارة
    Route::middleware('user.status')->group(function () {
        // سيتم إضافتها لاحقاً
    });
});
