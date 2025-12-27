<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\ApartmentReviewController;
use App\Http\Controllers\ReservationController;

Route::post('register', [AuthController::class, 'register']);

Route::post('login', [AuthController::class, 'login']);






Route::middleware('auth:sanctum')->group(function () {
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);


    Route::post('logout', [AuthController::class, 'logout']);


    Route::middleware(['user.status', 'profile.complete'])->group(function () {
        Route::get('user', [AuthController::class, 'user']);
    });

    // إنشاء طلب حجز جديد (مستأجر)
    Route::post('/reservations', [ReservationController::class, 'store']);

    // موافقة صاحب الشقة على الحجز
    Route::post('/reservations/{id}/approve', [ReservationController::class, 'approve']);

    // إلغاء الحجز (مستأجر)
    Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);

    // استعراض حجوزات المستخدم (مستأجر)
    Route::get('/reservations/my', [ReservationController::class, 'myReservations']);

    // استعراض الحجوزات الخاصة بالشقة (صاحب الشقة)
    Route::get('/apartments/{id}/reservations', [ReservationController::class, 'apartmentReservations']);

    // طلب تعديل الحجز (مستأجر)
    Route::post('/reservations/{id}/modify', [ReservationController::class, 'modify']);

    Route::post('/reservations/{id}/review', [ApartmentReviewController::class, 'review']);

    Route::post('/reservations/{id}/reject', [ReservationController::class, 'reject']);

});
