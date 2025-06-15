# Flashlight Payment System Documentation

## Overview

Sistem pembayaran untuk Flashlight Car & Motorcycle Wash dengan support untuk metode pembayaran Cash dan QRIS.

## Features Implemented

### 1. Payment Processing Resource

**File:** `app/Filament/Cashier/Resources/PaymentResource.php`

**Fitur:**

-   Menampilkan tabel transaksi yang siap untuk dibayar (status: completed, belum ada payment record)
-   Kolom tabel: No, Motorbike, Customer Name, Additional Services, Food and Drinks, Total Amount, Date
-   Tombol "Pay Now" untuk setiap transaksi
-   Modal dialog untuk memilih metode pembayaran (Cash/QRIS)

**Proses Cash Payment:**

-   Form input nominal uang yang dibayar
-   Perhitungan kembalian otomatis
-   Validasi minimum pembayaran
-   Pembayaran otomatis tersimpan

**Proses QRIS Payment:**

-   Generate QR code untuk customer
-   Monitoring status pembayaran
-   Update status otomatis setelah pembayaran berhasil

### 2. QRIS Payment Management

**File:** `app/Filament/Cashier/Resources/QRISPaymentResource.php`

**Fitur:**

-   Monitor semua transaksi QRIS
-   Check status payment manual
-   View QR code untuk transaksi pending
-   Auto-refresh setiap 30 detik

### 3. QRIS Integration

**File:** `app/Services/QRISService.php`

**Fitur:**

-   Generate QR code
-   Format QRIS string sesuai standar
-   Check status pembayaran
-   Process payment completion
-   Error handling dan logging

### 4. Payment Model

**File:** `app/Models/Payment.php`

**Database Fields:**

-   wash_transaction_id (Foreign Key)
-   staff_id (Foreign Key)
-   payment_number (Auto-generated)
-   method (cash/qris)
-   amount_paid
-   change_amount
-   qris_transaction_id
-   status (pending/completed/failed)
-   receipt_data (JSON)
-   paid_at (Timestamp)

**Methods:**

-   isCash(), isQris()
-   isPending(), isCompleted(), isFailed()
-   Static options untuk form

## Navigation Structure

**Cashier Panel:**

1. **Payment Processing** - Proses pembayaran transaksi baru
2. **QRIS Payments** - Monitor dan manage pembayaran QRIS

## QRIS Flow

1. Customer memilih QRIS payment
2. System generate QR code dengan detail transaksi
3. Customer scan QR code dengan mobile banking
4. System create payment record dengan status "pending"
5. Cashier dapat check status payment manual
6. Setelah payment completed, auto-update status

## Cash Payment Flow

1. Customer memilih cash payment
2. Cashier input nominal uang yang dibayar
3. System calculate kembalian otomatis
4. Validasi minimum pembayaran
5. Create payment record dengan status "completed"
6. Update transaction status

## Database Schema

### payments table

```sql
CREATE TABLE payments (
    id BIGINT PRIMARY KEY,
    wash_transaction_id BIGINT FOREIGN KEY,
    staff_id BIGINT FOREIGN KEY,
    payment_number VARCHAR UNIQUE,
    method ENUM('cash', 'qris'),
    amount_paid DECIMAL(10,2),
    change_amount DECIMAL(10,2) DEFAULT 0,
    qris_transaction_id VARCHAR NULLABLE,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    receipt_data JSON NULLABLE,
    paid_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Receipt Data Format

**Header:**

-   Business name: FLASHLIGHT WASH
-   Tagline: Car & Motorcycle Wash

**Transaction Info:**

-   Transaction number
-   Payment number
-   Date & time
-   Cashier name

**Customer Info:**

-   Customer name
-   Vehicle type
-   License plate

**Services:**

-   Primary service
-   Additional services
-   Food & drinks

**Payment Details:**

-   Subtotal
-   Total amount
-   Payment method
-   Cash amount (if cash)
-   Change amount (if cash)

**Footer:**

-   Thank you message

## Services Configuration

Add to `config/services.php`:

```php
'qris' => [
    'merchant_id' => env('QRIS_MERCHANT_ID', '1234567890'),
    'merchant_name' => env('QRIS_MERCHANT_NAME', 'FLASHLIGHT WASH'),
],
```

## Installation & Setup

1. Run migrations:

```bash
php artisan migrate
```

2. Configure QRIS merchant settings

## Future Enhancements

1. **QRIS Provider Integration**: Connect with real QRIS provider (DANA, OVO, etc.)
2. **Payment Analytics**: Dashboard untuk analisis pembayaran
3. **SMS/Email Receipt**: Digital receipt options
4. **Payment History Export**: Export payment reports

## Troubleshooting

### QRIS Issues

-   Verify merchant configuration
-   Check network connection
-   Monitor payment status regularly
-   Handle timeout scenarios

### Payment Issues

-   Validate amount calculations
-   Check foreign key constraints
-   Monitor transaction status
-   Handle concurrent payments
