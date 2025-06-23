# Send Transaction Data - Updated

## Overview

Endpoint `POST /api/payment/receive-transaction-data` telah diupdate untuk menghilangkan `change_amount` dari request body. Change amount sekarang dihitung otomatis di backend.

## Updated Request Body

### **Sebelum (Old):**

```json
{
    "total_amount": 75000,
    "payment_method": "cash",
    "wash_transaction_id": 45,
    "amount_paid": 100000,
    "change_amount": 25000, // ❌ Tidak perlu lagi
    "customer_id": 12,
    "notes": "Pembayaran tunai"
}
```

### **Sesudah (New):**

```json
{
    "total_amount": 75000,
    "payment_method": "cash",
    "wash_transaction_id": 45,
    "amount_paid": 100000, // ✅ Change amount dihitung otomatis
    "customer_id": 12,
    "notes": "Pembayaran tunai"
}
```

## Contoh Request Body

### 1. **Cash Payment:**

```json
{
    "total_amount": 75000,
    "payment_method": "cash",
    "wash_transaction_id": 45,
    "amount_paid": 100000,
    "customer_id": 12,
    "notes": "Pembayaran tunai"
}
```

**Backend akan menghitung:** `change_amount = 100000 - 75000 = 25000`

### 2. **Transfer Payment:**

```json
{
    "total_amount": 50000,
    "payment_method": "transfer",
    "wash_transaction_id": 46,
    "customer_id": 13,
    "notes": "Transfer BCA"
}
```

**Backend akan set:** `amount_paid = 50000`, `change_amount = 0`

### 3. **E-Wallet Payment:**

```json
{
    "total_amount": 65000,
    "payment_method": "e_wallet",
    "wash_transaction_id": 47,
    "customer_id": 14,
    "notes": "Pembayaran GoPay"
}
```

**Backend akan set:** `amount_paid = 65000`, `change_amount = 0`

## Backend Logic

```php
// Calculate change amount for cash payments
$amountPaid = $request->amount_paid ?? $request->total_amount;
$changeAmount = 0;

if ($request->payment_method === 'cash' && $amountPaid > $request->total_amount) {
    $changeAmount = $amountPaid - $request->total_amount;
}
```

## Response Format

**Success Response:**

```json
{
    "success": true,
    "message": "Transaction payment received and processed successfully",
    "data": {
        "payment": {
            "id": 23,
            "wash_transaction_id": 45,
            "method": "cash",
            "amount_paid": 100000,
            "change_amount": 25000, // ✅ Dihitung otomatis di backend
            "status": "completed",
            "paid_at": "2024-12-15T14:30:25Z"
        },
        "message": "Payment processed successfully and notification sent to customer."
    }
}
```

## Get Transaction Data

Untuk mendapatkan data termasuk `change_amount`, gunakan endpoint:

**GET** `/api/payment/ongoing-transaction-data?wash_transaction_id=45`

**Response:**

```json
{
    "success": true,
    "message": "Transaction data retrieved successfully",
    "data": {
        "wash_transaction_id": 45,
        "transaction_number": "TRX-20241215-001",
        "total_amount": 75000,
        "amount_paid": 100000,
        "total_change": 25000, // ✅ Change amount tersedia di sini
        "payment_method": "cash",
        "is_print_receipt": true,
        "customer_name": "John Doe",
        "completed_at": "2024-12-15T14:30:25Z"
    }
}
```

## FCM Notification

FCM notification tetap dikirim dengan format:

```json
{
    "data": {
        "is_print_receipt": true,
        "wash_transaction_id": "45"
    }
}
```

Mobile app dapat menggunakan `wash_transaction_id` untuk fetch complete transaction data yang sudah include `change_amount`.

## Benefits

1. **Simplified API**: Client tidak perlu menghitung change amount
2. **Data Consistency**: Backend memastikan calculation yang benar
3. **Reduced Errors**: Mengurangi kemungkinan error perhitungan di client
4. **Single Source of Truth**: Backend sebagai satu-satunya yang menghitung change

## Migration

**Existing Integration:**

-   Hapus `change_amount` dari request body mobile app
-   Keep logic untuk membaca `change_amount` dari response `getOngoingTransactionData`
-   Backend change calculation otomatis handled

**Testing:**

-   Test cash payment dengan amount_paid > total_amount
-   Test transfer/e-wallet payment (change_amount should be 0)
-   Verify FCM notification masih berfungsi
-   Verify getOngoingTransactionData return correct change_amount
