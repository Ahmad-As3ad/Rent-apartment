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

Route::middleware(['auth:sanctum', 'user.status', 'profile.complete'])->group(function () {
    Route::prefix('apartments')->group(function () {
        Route::get('/', [ApartmentController::class, 'index']);

        Route::get('/{id}', [ApartmentController::class, 'show']);

        Route::get('/cities/list', [ApartmentController::class, 'getCities']);
        Route::get('/price-range', [ApartmentController::class, 'getPriceRange']);
        Route::get('/rooms-options', [ApartmentController::class, 'getRoomsOptions']);

    });

});
