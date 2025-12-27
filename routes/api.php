<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\ApartmentReviewController;
use App\Http\Controllers\ReservationController;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::prefix('apartments')->group(function () {
    Route::get('/', [ApartmentController::class, 'index']);
    Route::get('/{id}', [ApartmentController::class, 'show']);
    Route::get('/cities/list', [ApartmentController::class, 'getCities']);
    Route::get('/price-range', [ApartmentController::class, 'getPriceRange']);
    Route::get('/rooms-options', [ApartmentController::class, 'getRoomsOptions']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);
});

Route::middleware(['auth:sanctum', 'user.status', 'profile.complete'])->group(function () {

<<<<<<< HEAD
    Route::middleware(['user.status', 'profile.complete'])->group(function () {
        Route::get('user', [AuthController::class, 'user']);
=======
    Route::get('user', [AuthController::class, 'user']);

    Route::prefix('apartments')->group(function () {

        Route::get('/my/list', [ApartmentController::class, 'myApartments']);

        Route::post('/', [ApartmentController::class, 'store']);
        Route::put('/{id}', [ApartmentController::class, 'update']);
        Route::delete('/{id}', [ApartmentController::class, 'destroy']);
    });
});
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    Route::prefix('users')->group(function () {
        Route::get('/', [AdminController::class, 'getUsersForReview']);
        Route::get('/{id}', [AdminController::class, 'getUserDetails']);
        Route::post('/{id}/approve', [AdminController::class, 'approveUser']);
        Route::post('/{id}/reject', [AdminController::class, 'rejectUser']);
        Route::post('/{id}/suspend', [AdminController::class, 'suspendUser']);
    });

    Route::prefix('apartments')->group(function () {
        Route::get('/pending', [AdminController::class, 'manageApartments']);
        Route::post('/{id}/approve', [AdminController::class, 'approveApartment']);
        Route::post('/{id}/reject', [AdminController::class, 'rejectApartment']);
>>>>>>> c6a913574c8c587b66545776dff5b02ffc1d25c2
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
