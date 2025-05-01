<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MembershipTypeController;
use App\Http\Controllers\Api\ServiceTypeCategoryController;
use App\Http\Controllers\Api\ServiceTypeController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\UserVehicleController;
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
    | Service Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceTypeController::class, 'index']);
        Route::post('/', [ServiceTypeController::class, 'store']);
        Route::get('/{id}', [ServiceTypeController::class, 'show']);
        Route::put('/{id}', [ServiceTypeController::class, 'update']);
        Route::delete('/{id}', [ServiceTypeController::class, 'destroy']);
        Route::post('/{id}/restore', [ServiceTypeController::class, 'restore']);
    });

    /*
    |--------------------------------------------------------------------------
    | Staff Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('staff')->group(function () {
        Route::get('members', [StaffController::class, 'index']);
        Route::post('members', [StaffController::class, 'store']);
        Route::get('members/{id}', [StaffController::class, 'show']);
        Route::put('members/{id}', [StaffController::class, 'update']);
        Route::delete('members/{id}', [StaffController::class, 'destroy']);
        Route::post('members/{id}/restore', [StaffController::class, 'restore']);
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
    | Vehicle Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('user-vehicles')->group(function () {
        Route::get('/', [UserVehicleController::class, 'index']);
        Route::get('/user/{userId}', [UserVehicleController::class, 'getByUserId']);
        Route::get('/vehicle/{vehicleId}', [UserVehicleController::class, 'getByVehicleId']);
        Route::post('', [UserVehicleController::class, 'store']);
        Route::get('/{id}', [UserVehicleController::class, 'show']);
        Route::put('/{id}', [UserVehicleController::class, 'update']);
        Route::delete('/{id}', [UserVehicleController::class, 'destroy']);
        Route::post('/{id}/restore', [UserVehicleController::class, 'restore']);
    });
    /*
    |--------------------------------------------------------------------------
    | Wash Transaction Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('transactions')->group(function () {
        Route::get('washes', [WashTransactionController::class, 'index']);
        Route::post('washes', [WashTransactionController::class, 'store']);
        Route::get('washes/{id}', [WashTransactionController::class, 'show']);
        Route::put('washes/{id}', [WashTransactionController::class, 'update']);
        Route::post('washes/{id}/restore', [WashTransactionController::class, 'restore']);
        Route::post('washes/{id}/complete', [WashTransactionController::class, 'complete']);
        Route::post('washes/{id}/cancel', [WashTransactionController::class, 'cancel']);
        Route::delete('washes/{id}', [WashTransactionController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Service Type Category Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('service-categories')->group(function () {
        Route::get('/', [ServiceTypeCategoryController::class, 'index']);
        Route::post('/', [ServiceTypeCategoryController::class, 'store']);
        Route::get('/{id}', [ServiceTypeCategoryController::class, 'show']);
        Route::put('/{id}', [ServiceTypeCategoryController::class, 'update']);
        Route::delete('/{id}', [ServiceTypeCategoryController::class, 'destroy']);
        Route::post('/{id}/restore', [ServiceTypeCategoryController::class, 'restore']);
    });
});
