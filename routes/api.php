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
use App\Http\Controllers\Api\TransactionPaymentController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\WorkOrderController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\FcmTokenController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-with-fcm', [AuthController::class, 'loginWithFcm']); // New simplified login endpoint
    Route::get('/fcm-token', [AuthController::class, 'getFcmToken'])->middleware('auth:sanctum'); // Get FCM token
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/device-logout', [AuthController::class, 'deviceLogout'])->middleware('auth:sanctum'); // New device-specific logout
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
        Route::patch('/{id}/fcm-token', [UserController::class, 'updateFcmToken']);
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
    | Product Management Routes (legacy) — disabled after total migration
    |--------------------------------------------------------------------------
    */
    // Route::prefix('products')->group(function () {
    //     Route::get('/', [ProductController::class, 'index']);
    //     Route::post('/', [ProductController::class, 'store']);
    //     Route::get('/{id}', [ProductController::class, 'show']);
    //     Route::put('/{id}', [ProductController::class, 'update']);
    //     Route::delete('/{id}', [ProductController::class, 'destroy']);
    //     Route::post('/{id}/restore', [ProductController::class, 'restore']);
    // });

    /*
    |--------------------------------------------------------------------------
    | Product Category Management Routes (legacy) — disabled after total migration
    |--------------------------------------------------------------------------
    */
    // Route::prefix('product-categories')->group(function () {
    //     Route::get('/', [ProductCategoryController::class, 'index']);
    //     Route::post('/', [ProductCategoryController::class, 'store']);
    //     Route::get('/{id}', [ProductCategoryController::class, 'show']);
    //     Route::put('/{id}', [ProductCategoryController::class, 'update']);
    //     Route::delete('/{id}', [ProductCategoryController::class, 'destroy']);
    //     Route::post('/{id}/restore', [ProductCategoryController::class, 'restore']);
    // });

    /*
    |--------------------------------------------------------------------------
    | Catalog Routes (Service Items, Price Matrix, F&D)
    |--------------------------------------------------------------------------
    */
    Route::prefix('catalog')->group(function () {
        Route::get('/services', [\App\Http\Controllers\Api\ServiceItemController::class, 'index']);
        Route::post('/services', [\App\Http\Controllers\Api\ServiceItemController::class, 'store']);
        Route::get('/services/{serviceItem}', [\App\Http\Controllers\Api\ServiceItemController::class, 'show']);
        Route::put('/services/{serviceItem}', [\App\Http\Controllers\Api\ServiceItemController::class, 'update']);
        Route::delete('/services/{serviceItem}', [\App\Http\Controllers\Api\ServiceItemController::class, 'destroy']);

        Route::get('/price-matrix', [\App\Http\Controllers\Api\PriceMatrixController::class, 'index']);
        Route::post('/price-matrix', [\App\Http\Controllers\Api\PriceMatrixController::class, 'store']);
        Route::put('/price-matrix/{priceMatrix}', [\App\Http\Controllers\Api\PriceMatrixController::class, 'update']);
        Route::delete('/price-matrix/{priceMatrix}', [\App\Http\Controllers\Api\PriceMatrixController::class, 'destroy']);

        Route::get('/fd-items', [\App\Http\Controllers\Api\FdItemController::class, 'index']);
        Route::post('/fd-items', [\App\Http\Controllers\Api\FdItemController::class, 'store']);
        Route::get('/fd-items/{fdItem}', [\App\Http\Controllers\Api\FdItemController::class, 'show']);
        Route::put('/fd-items/{fdItem}', [\App\Http\Controllers\Api\FdItemController::class, 'update']);
        Route::delete('/fd-items/{fdItem}', [\App\Http\Controllers\Api\FdItemController::class, 'destroy']);

        // Dimensions
        Route::get('/engine-classes', [\App\Http\Controllers\Api\DimensionController::class, 'listEngineClasses']);
        Route::post('/engine-classes', [\App\Http\Controllers\Api\DimensionController::class, 'createEngineClass']);
        Route::put('/engine-classes/{engineClass}', [\App\Http\Controllers\Api\DimensionController::class, 'updateEngineClass']);
        Route::delete('/engine-classes/{engineClass}', [\App\Http\Controllers\Api\DimensionController::class, 'deleteEngineClass']);
        Route::get('/helmet-types', [\App\Http\Controllers\Api\DimensionController::class, 'listHelmetTypes']);
        Route::post('/helmet-types', [\App\Http\Controllers\Api\DimensionController::class, 'createHelmetType']);
        Route::put('/helmet-types/{helmetType}', [\App\Http\Controllers\Api\DimensionController::class, 'updateHelmetType']);
        Route::delete('/helmet-types/{helmetType}', [\App\Http\Controllers\Api\DimensionController::class, 'deleteHelmetType']);
        Route::get('/car-sizes', [\App\Http\Controllers\Api\DimensionController::class, 'listCarSizes']);
        Route::post('/car-sizes', [\App\Http\Controllers\Api\DimensionController::class, 'createCarSize']);
        Route::put('/car-sizes/{carSize}', [\App\Http\Controllers\Api\DimensionController::class, 'updateCarSize']);
        Route::delete('/car-sizes/{carSize}', [\App\Http\Controllers\Api\DimensionController::class, 'deleteCarSize']);
        Route::get('/apparel-types', [\App\Http\Controllers\Api\DimensionController::class, 'listApparelTypes']);
        Route::post('/apparel-types', [\App\Http\Controllers\Api\DimensionController::class, 'createApparelType']);
        Route::put('/apparel-types/{apparelType}', [\App\Http\Controllers\Api\DimensionController::class, 'updateApparelType']);
        Route::delete('/apparel-types/{apparelType}', [\App\Http\Controllers\Api\DimensionController::class, 'deleteApparelType']);
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
        Route::get('/license-plate/{licensePlate}', [CustomerVehicleController::class, 'getByLicensePlate']);
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
        Route::get('/customer/{customerId}', [WashTransactionController::class, 'getByCustomerId']);
        Route::post('/', [WashTransactionController::class, 'store']);
        Route::get('/{id}', [WashTransactionController::class, 'show']);
        Route::put('/{id}', [WashTransactionController::class, 'update']);
        Route::post('/{id}/restore', [WashTransactionController::class, 'restore']);
        Route::post('/{id}/complete', [WashTransactionController::class, 'complete']);
        Route::post('/{id}/cancel', [WashTransactionController::class, 'cancel']);
        Route::delete('/{id}', [WashTransactionController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Work Orders (Kiosk) Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('work-orders')->group(function () {
        Route::get('/', [WorkOrderController::class, 'index']);
        Route::post('/', [WorkOrderController::class, 'store']);
        Route::get('/{workOrder}', [WorkOrderController::class, 'show']);
        Route::put('/{workOrder}', [WorkOrderController::class, 'update']);
        Route::post('/quote', [WorkOrderController::class, 'quote']);
    });

    Route::get('/trx-next-number', [WashTransactionController::class, 'getNextTransactionNumber']);
    Route::get('/trx-prev-number', [WashTransactionController::class, 'getPreviousTransactionNumber']);

    /*
    |--------------------------------------------------------------------------
    | Shift Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('shifts')->group(function () {
        Route::post('/start', [ShiftController::class, 'start']);
        Route::post('/end', [ShiftController::class, 'end']);
        Route::get('/current', [ShiftController::class, 'current']);
        Route::get('/status', [ShiftController::class, 'status']);
        Route::get('/history', [ShiftController::class, 'history']);
        Route::get('/{id}', [ShiftController::class, 'show']);
        Route::get('/{id}/transactions', [ShiftController::class, 'transactions']);
    });

    /*
    |--------------------------------------------------------------------------
    | FCM Token Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('fcm')->group(function () {
        Route::patch('/token', [FcmTokenController::class, 'updateToken']);
        Route::delete('/token', [FcmTokenController::class, 'removeToken']);
    });

    /*
    |--------------------------------------------------------------------------
    | FCM Notification Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('fcm')->group(function () {
        Route::post('/send', [FCMController::class, 'sendNotification']);
        Route::post('/send-multiple', [FCMController::class, 'sendMultipleNotifications']);
        Route::get('/test', [FCMController::class, 'testConnection']);
        Route::get('/config', [FCMController::class, 'getConfigStatus']);
    });

    /*
    |--------------------------------------------------------------------------
    | Transaction Payment Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('payment')->group(function () {
        Route::post('/send-transaction-data', [TransactionPaymentController::class, 'sendTransactionData']);
        Route::get('/ongoing-transaction-data', [TransactionPaymentController::class, 'getOngoingTransactionData']);
    });
});
