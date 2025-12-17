<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApartmentController;

//post هاد الراوت بيستخدم ال
Route::post('register', [AuthController::class, 'register']);

//post هاد الراوت بيستخدم ال
Route::post('login', [AuthController::class, 'login']);






Route::middleware('auth:sanctum')->group(function () {
// هاد يلي منأدخل من باقي المعلومات
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);


    Route::post('logout', [AuthController::class, 'logout']);


    Route::middleware(['user.status', 'profile.complete'])->group(function () {
//هاد بيرجع معلومات اليوزر بس اذا ماكنا مأدخلين المعلومات كاملة رح يطلع رسالة انو لازم نأدخل المعلومات
        Route::get('user', [AuthController::class, 'user']);

        Route::prefix(prefix: 'apartments')->group(function () {

            Route::get('/', [ApartmentController::class, 'index']);
            Route::get('/{id}', [ApartmentController::class, 'show']);
            Route::get('/cities/list', [ApartmentController::class, 'getCities']);
            Route::get('/regions/list', [ApartmentController::class, 'getRegions']);
            Route::get('/filters/options', [ApartmentController::class, 'getFilterOptions']);

            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/', [ApartmentController::class, 'store']);
                Route::put('/{id}', [ApartmentController::class, 'update']);
            });
        });

    });
});
