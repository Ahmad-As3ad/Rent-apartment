<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\ApartmentReviewController;
use App\Http\Controllers\ReservationController;




// Authentication routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

//  فلترة
Route::prefix('apartments')->group(function () {
    Route::get('/', [ApartmentController::class, 'index']);
    Route::get('/{id}', [ApartmentController::class, 'show']);
    Route::get('/cities/list', [ApartmentController::class, 'getCities']);
    Route::get('/price-range', [ApartmentController::class, 'getPriceRange']);
    Route::get('/rooms-options', [ApartmentController::class, 'getRoomsOptions']);
});

// PROTECTED ROUTES (Authentication required)

Route::middleware('auth:sanctum')->group(function () {
    // Basic authentication routes
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);
});


// PROTECTED ROUTES WITH COMPLETE PROFILE

Route::middleware(['auth:sanctum'])->group(function () {

    // Get current user info
    Route::get('user', [AuthController::class, 'user']);

    // Apartment management routes
    Route::prefix('apartments')->group(function () {
        Route::get('/my/list', [ApartmentController::class, 'myApartments']);
        Route::post('/', [ApartmentController::class, 'store']);
        Route::put('/{id}', [ApartmentController::class, 'update']);
        Route::delete('/{id}', [ApartmentController::class, 'destroy']);
    });

    // Reservation routes (for tenants and owners)
    Route::prefix('reservations')->group(function () {
        // Create new reservation (tenant only)
        Route::post('/', [ReservationController::class, 'store']);

        // Get user's reservations (tenant only)
        Route::get('/my', [ReservationController::class, 'myReservations']);

        // Reservation actions
        Route::post('/{id}/cancel', [ReservationController::class, 'cancel']);
        Route::post('/{id}/modify', [ReservationController::class, 'modify']);
        Route::post('/{id}/review', [ApartmentReviewController::class, 'review']);
    });

    // Apartment reservations for owners
    Route::prefix('apartments/{id}/reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'apartmentReservations']);
        Route::post('/{reservation_id}/approve', [ReservationController::class, 'approve']);
        Route::post('/{reservation_id}/reject', [ReservationController::class, 'reject']);
    });
});

// ADMIN ROUTES

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {


    // User management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminController::class, 'getUsersForReview']);
        Route::get('/{id}', [AdminController::class, 'getUserDetails']);
        Route::post('/{id}/approve', [AdminController::class, 'approveUser']);
        Route::post('/{id}/reject', [AdminController::class, 'rejectUser']);
        Route::post('/{id}/suspend', [AdminController::class, 'suspendUser']);
    });

    // Apartment management
    Route::prefix('apartments')->group(function () {
        Route::get('/pending', [AdminController::class, 'manageApartments']);
        Route::post('/{id}/approve', [AdminController::class, 'approveApartment']);
        Route::post('/{id}/reject', [AdminController::class, 'rejectApartment']);
    });
});
