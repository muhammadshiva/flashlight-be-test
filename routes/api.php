<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MembershipTypeController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\CustomerVehicleController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\WashTransactionController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | User Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::get('/phone/{phoneNumber}', [UserController::class, 'getByPhoneNumber']);
    });

    /*
    |--------------------------------------------------------------------------
    | Membership Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('memberships')->group(function () {
        Route::get('/', [MembershipTypeController::class, 'index']);
        Route::post('/', [MembershipTypeController::class, 'store']);
        Route::get('/{id}', [MembershipTypeController::class, 'show']);
        Route::put('/{id}', [MembershipTypeController::class, 'update']);
        Route::delete('/{id}', [MembershipTypeController::class, 'destroy']);
        Route::post('/{id}/restore', [MembershipTypeController::class, 'restore']);
    });

    /*
    |--------------------------------------------------------------------------
    | Product Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/{id}/restore', [ProductController::class, 'restore']);
    });

    /*
    |--------------------------------------------------------------------------
    | Product Category Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('product-categories')->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index']);
        Route::post('/', [ProductCategoryController::class, 'store']);
        Route::get('/{id}', [ProductCategoryController::class, 'show']);
        Route::put('/{id}', [ProductCategoryController::class, 'update']);
        Route::delete('/{id}', [ProductCategoryController::class, 'destroy']);
        Route::post('/{id}/restore', [ProductCategoryController::class, 'restore']);
    });

    /*
    |--------------------------------------------------------------------------
    | Staff Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('staff')->group(function () {
        Route::get('/', [StaffController::class, 'index']);
        Route::post('/', [StaffController::class, 'store']);
        Route::get('/{id}', [StaffController::class, 'show']);
        Route::put('/{id}', [StaffController::class, 'update']);
        Route::delete('/{id}', [StaffController::class, 'destroy']);
        Route::post('/{id}/restore', [StaffController::class, 'restore']);
    });

    /*
    |--------------------------------------------------------------------------
    | Vehicle Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index']);
        Route::post('/', [VehicleController::class, 'store']);
        Route::get('/{id}', [VehicleController::class, 'show']);
        Route::put('/{id}', [VehicleController::class, 'update']);
        Route::delete('/{id}', [VehicleController::class, 'destroy']);
        Route::post('/{id}/restore', [VehicleController::class, 'restore']);
    });
    /*
    |--------------------------------------------------------------------------
    | Customer Vehicle Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('customer-vehicles')->group(function () {
        Route::get('/', [CustomerVehicleController::class, 'index']);
        Route::get('/customer/{customerId}', [CustomerVehicleController::class, 'getByCustomerId']);
        Route::get('/vehicle/{vehicleId}', [CustomerVehicleController::class, 'getByVehicleId']);
        Route::post('', [CustomerVehicleController::class, 'store']);
        Route::get('/{id}', [CustomerVehicleController::class, 'show']);
        Route::put('/{id}', [CustomerVehicleController::class, 'update']);
        Route::delete('/{id}', [CustomerVehicleController::class, 'destroy']);
        Route::post('/{id}/restore', [CustomerVehicleController::class, 'restore']);
    });
    /*
    |--------------------------------------------------------------------------
    | Wash Transaction Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('transactions')->group(function () {
        Route::get('/', [WashTransactionController::class, 'index']);
        Route::post('/', [WashTransactionController::class, 'store']);
        Route::get('/{id}', [WashTransactionController::class, 'show']);
        Route::put('/{id}', [WashTransactionController::class, 'update']);
        Route::post('/{id}/restore', [WashTransactionController::class, 'restore']);
        Route::post('/{id}/complete', [WashTransactionController::class, 'complete']);
        Route::post('/{id}/cancel', [WashTransactionController::class, 'cancel']);
        Route::delete('/{id}', [WashTransactionController::class, 'destroy']);
    });
});
