# Cashier Payment Processing Update

## Overview

Update sistem payment processing kasir untuk mendukung metode pembayaran dari aplikasi mobile dengan FCM notification integration.

## Perubahan yang Dibuat

### 1. **Tambahan Payment Method 'Transfer'**

**File yang diubah:**

-   `app/Models/WashTransaction.php` - Tambah konstanta dan method untuk transfer
-   `database/migrations/2025_06_19_014040_add_transfer_payment_method_to_wash_transactions_table.php` - Migration baru
-   `app/Http/Controllers/Api/WashTransactionController.php` - Update validasi

**Payment Methods yang didukung:**

-   `cash` - Pembayaran tunai
-   `transfer` - Transfer bank
-   `cashless` - Cashless (existing)
-   `qris` - QRIS (disabled)

### 2. **Update Cashier Payment Interface**

**File:** `app/Filament/Cashier/Resources/PaymentResource.php`

**Perubahan UI:**

-   Tambah kolom "Payment Method" di tabel dengan badge berwarna
-   Form payment method menampilkan pilihan yang dipilih customer dari mobile app
-   Helper text menunjukkan payment method yang dipilih customer
-   Tombol submit berubah sesuai payment method:
    -   Cash: "Process Cash Payment"
    -   Transfer: "Confirm Transfer Received"
    -   QRIS: "QRIS Not Available"

**Logic Processing:**

-   **Cash**: Tetap menggunakan popup konfirmasi existing dengan input amount paid dan change calculation
-   **Transfer**: Tombol konfirmasi untuk kasir memverifikasi transfer telah diterima
-   **QRIS**: Diabaikan dengan notifikasi warning

### 3. **FCM Notification Integration**

**Automatic Notifications dikirim setelah:**

-   Cash payment berhasil diproses
-   Transfer payment dikonfirmasi oleh kasir

**Format Notification:**

#### Cash Payment:

```json
{
    "title": "Payment Completed Successfully",
    "body": "Thank you John! Your cash payment has been processed successfully. Amount paid: IDR 75,000, Change: IDR 25,000",
    "data": {
        "type": "payment_completed",
        "payment_method": "cash",
        "payment_id": "123",
        "transaction_id": "45",
        "amount_paid": "100000",
        "change_amount": "25000",
        "customer_name": "John Doe",
        "transaction_number": "TRX-20241215-001",
        "receipt_number": "123"
    }
}
```

#### Transfer Payment:

```json
{
    "title": "Transfer Payment Confirmed",
    "body": "Thank you John! Your bank transfer payment has been confirmed by our cashier. Amount: IDR 75,000",
    "data": {
        "type": "payment_completed",
        "payment_method": "transfer",
        "payment_id": "124",
        "transaction_id": "45",
        "amount_paid": "75000",
        "change_amount": "0",
        "customer_name": "John Doe",
        "transaction_number": "TRX-20241215-001",
        "receipt_number": "124"
    }
}
```

### 4. **Payment Method Detection**

**Cashier Interface Features:**

-   Payment method dari mobile app ditampilkan sebagai badge di tabel
-   Form payment method auto-select sesuai pilihan customer:

    -   `cash` → Cash
    -   `cashless` → Transfer
    -   `transfer` → Transfer
    -   Default → Cash

-   Helper text: "Customer selected: Cash via mobile app"

### 5. **Database Schema Update**

**Migration:** `2025_06_19_014040_add_transfer_payment_method_to_wash_transactions_table`

```sql
-- Sebelum
ENUM('cash', 'cashless')

-- Sesudah
ENUM('cash', 'cashless', 'transfer')
```

## Workflow Pemrosesan Payment

### Cash Payment:

1. Kasir klik "Process Payment"
2. Form menampilkan payment method "Cash" (dari mobile app)
3. Kasir input amount paid
4. System calculate change otomatis
5. Konfirmasi payment
6. FCM notification dikirim ke customer

### Transfer Payment:

1. Kasir klik "Process Payment"
2. Form menampilkan payment method "Transfer" (dari mobile app)
3. Informasi ditampilkan: "Customer will transfer payment outside the system"
4. Kasir klik "Confirm Transfer Received" setelah verifikasi transfer
5. FCM notification dikirim ke customer

### QRIS Payment:

1. Kasir klik "Process Payment"
2. Jika pilih QRIS, muncul warning "QRIS Not Available"
3. Payment tidak diproses

## Error Handling

-   Validasi payment method yang tidak valid
-   FCM token tidak tersedia (customer tidak login)
-   FCM service error (logged dengan detail)
-   Database transaction rollback pada error

## Testing

**Test Scenarios:**

1. Payment dengan method Cash dari mobile app
2. Payment dengan method Transfer dari mobile app
3. Payment dengan method Cashless (mapped ke Transfer)
4. Payment tanpa FCM token
5. QRIS payment (should be rejected)

**Test FCM:**

```bash
# Test FCM connection
GET /api/fcm/test

# Test manual notification
POST /api/fcm/send
{
  "token": "customer_fcm_token",
  "title": "Test Payment",
  "body": "Test notification",
  "data": {"type": "test"}
}
```

## Monitoring & Logging

**FCM Notifications logged dengan:**

-   Customer ID dan nama
-   Transaction ID dan nomor
-   Payment method dan amount
-   Notification success/failure
-   Error details jika gagal

**Log Locations:**

-   `storage/logs/laravel.log` - General application logs
-   FCM specific logs dengan prefix `[FCM]`

## Security Considerations

-   Authentication required untuk semua payment operations
-   Staff ID tracking untuk audit trail
-   Transaction validation sebelum payment processing
-   Database transactions untuk data consistency

## Future Enhancements

1. **QRIS Integration**: Implementasi QRIS payment yang sesungguhnya
2. **E-Wallet Support**: Tambah support untuk e-wallet payments
3. **Real-time Updates**: WebSocket untuk real-time payment status
4. **Receipt Generation**: Auto-generate digital receipt
5. **Payment History**: Enhanced payment history tracking
