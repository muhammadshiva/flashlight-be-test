# Flashlight Payment System Documentation

## Overview

Sistem pembayaran untuk Flashlight Car & Motorcycle Wash dengan support untuk metode pembayaran Cash dan QRIS, terintegrasi dengan thermal printer Bluetooth.

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
-   Cetak struk otomatis melalui thermal printer

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
-   Print receipt untuk transaksi completed
-   Auto-refresh setiap 30 detik

### 3. Thermal Printer Integration

**File:** `app/Services/ThermalPrinterService.php`

**Fitur:**

-   Test koneksi printer Bluetooth
-   Format struk otomatis (58mm/80mm)
-   Print detail transaksi lengkap
-   Support untuk berbagai ukuran kertas
-   Logging untuk troubleshooting

**Printer Settings:**

-   Scan dan pilih device Bluetooth
-   Setting ukuran kertas (58mm/80mm)
-   Auto-print toggle
-   Test connection

### 4. QRIS Integration

**File:** `app/Services/QRISService.php`

**Fitur:**

-   Generate QR code
-   Format QRIS string sesuai standar
-   Check status pembayaran
-   Process payment completion
-   Error handling dan logging

### 5. Payment Model

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
-   receipt_printed (Boolean)
-   paid_at (Timestamp)

**Methods:**

-   isCash(), isQris()
-   isPending(), isCompleted(), isFailed()
-   Static options untuk form

## Navigation Structure

**Cashier Panel:**

1. **Payment Processing** - Proses pembayaran transaksi baru
2. **QRIS Payments** - Monitor dan manage pembayaran QRIS

## Printer Settings

**Available Actions:**

-   **Printer Settings**: Konfigurasi printer dan koneksi
-   **Test Printer**: Test koneksi dan print test
-   **Scan Devices**: Scan perangkat Bluetooth yang tersedia

**Supported Printers:**

-   Thermal Printer 58mm
-   Thermal Printer 80mm
-   Xprinter XP-58IIH
-   Generic Bluetooth thermal printers

## QRIS Flow

1. Customer memilih QRIS payment
2. System generate QR code dengan detail transaksi
3. Customer scan QR code dengan mobile banking
4. System create payment record dengan status "pending"
5. Cashier dapat check status payment manual
6. Setelah payment completed, auto-update status
7. Print receipt jika diperlukan

## Cash Payment Flow

1. Customer memilih cash payment
2. Cashier input nominal uang yang dibayar
3. System calculate kembalian otomatis
4. Validasi minimum pembayaran
5. Create payment record dengan status "completed"
6. Auto-print receipt jika enabled
7. Update transaction status

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
    receipt_printed BOOLEAN DEFAULT false,
    paid_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Receipt Format

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

## Configuration

### Session Storage

```php
session(['printer_settings' => [
    'printer_device' => 'device_id',
    'auto_print' => true,
    'printer_width' => 58
]]);
```

### Services Configuration

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

2. Ensure Bluetooth is available on server
3. Configure printer settings through UI
4. Test printer connection
5. Configure QRIS merchant settings

## Future Enhancements

1. **Real Bluetooth Integration**: Implement actual Bluetooth connection
2. **QRIS Provider Integration**: Connect with real QRIS provider (DANA, OVO, etc.)
3. **Receipt Customization**: Allow custom receipt templates
4. **Payment Analytics**: Dashboard untuk analisis pembayaran
5. **Multi-printer Support**: Support multiple printers
6. **SMS/Email Receipt**: Digital receipt options
7. **Payment History Export**: Export payment reports

## Troubleshooting

### Printer Issues

-   Check Bluetooth connection
-   Verify printer power
-   Test with different paper sizes
-   Check printer compatibility

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
