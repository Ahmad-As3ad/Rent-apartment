<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApartmentController;

Route::post('register', [AuthController::class, 'register']);

Route::post('login', [AuthController::class, 'login']);






Route::middleware('auth:sanctum')->group(function () {
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);


    Route::post('logout', [AuthController::class, 'logout']);


    Route::middleware(['user.status', 'profile.complete'])->group(function () {
        Route::get('user', [AuthController::class, 'user']);



    });
});
