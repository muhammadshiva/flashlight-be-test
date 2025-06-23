# FCM Payment Integration Implementation

## Overview

This document outlines the implementation of FCM (Firebase Cloud Messaging) integration for payment notifications in the Flashlight Car Wash backend system.

## Features Implemented

### 1. Modified Login Endpoint

**Endpoint:** `POST /api/auth/login`

**Updated Request Format:**

```json
{
    "email": "admin@mail.com",
    "password": "admin123",
    "fcm_token": "token_dari_perangkat"
}
```

**Changes Made:**

-   Added optional `fcm_token` field to login validation
-   Automatically updates user's FCM token upon successful login
-   FCM token is stored in the `users` table for future notifications

### 2. New Transaction Payment Controller

**File:** `app/Http/Controllers/Api/TransactionPaymentController.php`

This controller handles:

-   Receiving transaction data from clients after payment completion
-   Sending FCM notifications to customers
-   Retrieving ongoing transaction data

### 3. New API Endpoints

#### A. Receive Transaction Data

**Endpoint:** `POST /api/payment/receive-transaction-data`

**Purpose:** Receive and process transaction data from client after cashier completes payment

**Request Body:**

```json
{
    "total_amount": 50000,
    "payment_method": "cash",
    "wash_transaction_id": 123,
    "amount_paid": 60000,
    "change_amount": 10000,
    "customer_id": 45,
    "notes": "Optional notes"
}
```

**Supported Payment Methods:**

-   `cash` - Cash payment
-   `qris` - QRIS payment
-   `transfer` - Bank transfer
-   `e_wallet` - E-wallet payment

**Response:**

```json
{
    "success": true,
    "message": "Transaction payment received and processed successfully",
    "data": {
        "payment": {
            "id": 1,
            "wash_transaction_id": 123,
            "method": "cash",
            "amount_paid": 60000,
            "change_amount": 10000,
            "status": "completed",
            "paid_at": "2024-01-15T10:30:00Z"
        },
        "message": "Payment processed successfully and notification sent to customer."
    }
}
```

#### B. Get Ongoing Transaction Data

**Endpoint:** `GET /api/payment/ongoing-transaction-data`

**Purpose:** Retrieve transaction data for customers after receiving FCM notification

**Optional Query Parameters:**

-   `wash_transaction_id` - Specific transaction ID to retrieve

**Response:**

```json
{
    "success": true,
    "message": "Transaction data retrieved successfully",
    "data": {
        "wash_transaction_id": 123,
        "transaction_number": "TRX-20240115-001",
        "total_amount": 50000,
        "amount_paid": 60000,
        "total_change": 10000,
        "payment_method": "cash",
        "is_print_receipt": true,
        "customer_name": "John Doe",
        "vehicle_info": {
            "license_plate": "B1234XYZ",
            "vehicle_name": "Toyota Avanza"
        },
        "services": [
            {
                "name": "Cuci Mobil Premium",
                "price": 50000,
                "quantity": 1
            }
        ],
        "completed_at": "2024-01-15T10:30:00Z",
        "payment_completed_at": "2024-01-15T10:32:00Z"
    }
}
```

### 4. FCM Notification System

**Automatic Notifications:**

-   Sent when payment is completed and verified by cashier
-   Only sent to customers who have FCM tokens
-   Includes transaction details in notification data

**Notification Format:**

```json
{
    "title": "Pembayaran Selesai!",
    "body": "Pembayaran untuk transaksi #TRX-20240115-001 sebesar Rp 60.000 telah berhasil diverifikasi.",
    "data": {
        "type": "payment_completed",
        "wash_transaction_id": "123",
        "transaction_number": "TRX-20240115-001",
        "amount_paid": "60000",
        "change_amount": "10000",
        "payment_method": "cash",
        "completed_at": "2024-01-15T10:30:00Z"
    }
}
```

### 5. Enhanced Payment Model

**File:** `app/Models/Payment.php`

**New Payment Methods Added:**

-   `METHOD_TRANSFER = 'transfer'`
-   `METHOD_E_WALLET = 'e_wallet'`

**New Helper Methods:**

-   `isTransfer()` - Check if payment is bank transfer
-   `isEWallet()` - Check if payment is e-wallet

## Security Features

1. **Authentication Required:** All endpoints require valid Sanctum authentication
2. **Data Validation:** Comprehensive input validation for all requests
3. **Transaction Verification:** Validates transaction status before processing payments
4. **Database Transactions:** Uses database transactions for data consistency

## Error Handling

The system includes comprehensive error handling for:

-   Invalid transaction states
-   Missing FCM tokens
-   Database errors
-   Validation failures
-   FCM service failures

## Logging

All FCM notifications and payment processing events are logged for monitoring and debugging.

## Usage Flow

1. **Customer Login:** Customer logs in with FCM token
2. **Service Completion:** Cashier completes wash service and processes payment
3. **Payment Data Submission:** Client sends payment data to `/receive-transaction-data`
4. **FCM Notification:** System sends notification to customer's device
5. **Data Retrieval:** Customer app requests transaction details from `/ongoing-transaction-data`

## Notes

-   FCM tokens are automatically updated during login
-   Notifications are only sent to customers with valid FCM tokens
-   Receipt printing is determined based on payment method and change amount
-   System supports multiple payment methods for flexibility

## Known Issues

There are currently some linter warnings related to the Customer-User relationship in the TransactionPaymentController. These can be resolved by ensuring proper type hints and relationship definitions.

## Testing

To test the implementation:

1. Use the existing FCM test endpoints:

    - `GET /api/fcm/test` - Test FCM connection
    - `GET /api/fcm/config` - Check FCM configuration

2. Test the login endpoint with FCM token
3. Create a test transaction and use the payment endpoints
4. Verify FCM notifications are sent correctly
