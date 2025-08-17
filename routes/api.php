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
use App\Http\Controllers\Api\POSTransactionController;
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
        Route::get('/license-plate/{licensePlate}', [CustomerVehicleController::class, 'getByLicensePlate']);
        Route::post('', [CustomerVehicleController::class, 'store']);
        Route::get('/{id}', [CustomerVehicleController::class, 'show']);
        Route::put('/{id}', [CustomerVehicleController::class, 'update']);
        Route::delete('/{id}', [CustomerVehicleController::class, 'destroy']);
        Route::post('/{id}/restore', [CustomerVehicleController::class, 'restore']);
    });
    /*
    |--------------------------------------------------------------------------
    | Work Order Routes (Self Ordering Kiosk)
    |--------------------------------------------------------------------------
    */
    Route::prefix('work-orders')->group(function () {
        Route::get('/', [WorkOrderController::class, 'index']);
        Route::post('/', [WorkOrderController::class, 'store']);
        Route::get('/queue', [WorkOrderController::class, 'getQueue']);
        Route::get('/customer/{customerId}', [WorkOrderController::class, 'getByCustomerId']);
        Route::get('/{workOrder}', [WorkOrderController::class, 'show']);
        Route::put('/{workOrder}', [WorkOrderController::class, 'update']);
        Route::delete('/{workOrder}', [WorkOrderController::class, 'destroy']);
        Route::post('/{workOrder}/confirm', [WorkOrderController::class, 'confirm']); // Creates wash transaction
        Route::post('/{workOrder}/cancel', [WorkOrderController::class, 'cancel']);
    });

    /*
    |--------------------------------------------------------------------------
    | POS Transaction Routes (Point of Sales)
    |--------------------------------------------------------------------------
    */
    Route::prefix('pos-transactions')->group(function () {
        Route::get('/', [POSTransactionController::class, 'index']);
        Route::post('/', [POSTransactionController::class, 'store']); // Direct sales
        Route::get('/customer/{customerId}', [POSTransactionController::class, 'getByCustomerId']);
        Route::get('/sales-report', [POSTransactionController::class, 'getDailySalesReport']);
        Route::get('/{posTransaction}', [POSTransactionController::class, 'show']);
        Route::put('/{posTransaction}', [POSTransactionController::class, 'update']);
        Route::delete('/{posTransaction}', [POSTransactionController::class, 'destroy']);

        // Payment processing routes
        Route::post('/wash-transaction/{washTransaction}/payment', [POSTransactionController::class, 'processWashTransactionPayment']);
        Route::post('/work-order/{workOrder}/payment', [POSTransactionController::class, 'processWorkOrderPayment']); // Backward compatibility
    });

    /*
    |--------------------------------------------------------------------------
    | Wash Transaction Routes (Service Management)
    |--------------------------------------------------------------------------
    */
    Route::prefix('wash-transactions')->group(function () {
        Route::get('/', [WashTransactionController::class, 'index']);
        Route::get('/customer/{customerId}', [WashTransactionController::class, 'getByCustomerId']);
        Route::post('/', [WashTransactionController::class, 'store']); // Direct wash transaction
        Route::get('/service-queue', [WashTransactionController::class, 'getServiceQueue']);
        Route::get('/{washTransaction}', [WashTransactionController::class, 'show']);
        Route::put('/{washTransaction}', [WashTransactionController::class, 'update']);
        Route::delete('/{washTransaction}', [WashTransactionController::class, 'destroy']);

        // Service management routes
        Route::post('/{washTransaction}/start-service', [WashTransactionController::class, 'startService']);
        Route::post('/{washTransaction}/complete-service', [WashTransactionController::class, 'completeService']);
        Route::post('/{washTransaction}/complete', [WashTransactionController::class, 'complete']); // Backward compatibility
        Route::post('/{washTransaction}/cancel', [WashTransactionController::class, 'cancel']);
    });

    /*
    |--------------------------------------------------------------------------
    | Legacy Transaction Routes (Backward Compatibility)
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
