# Wash Status Management Feature

## Overview

Fitur ini memungkinkan role cashier untuk mengubah status wash pada transaksi pencucian. Status ini digunakan untuk tracking proses pencucian apakah masih pending, in progress, atau complete.

## Status Wash Transaction

-   **Pending**: Proses pencucian belum ditangani
-   **In Progress**: Proses pencucian sedang dikerjakan
-   **Complete**: Proses pencucian telah selesai

## Fitur yang Ditambahkan

### 1. WashStatusResource

-   **File**: `app/Filament/Cashier/Resources/WashStatusResource.php`
-   **Fungsi**: Resource untuk mengelola status wash transaction
-   **Fitur**:
    -   Menampilkan daftar transaksi dengan detail lengkap
    -   Dropdown untuk mengubah status wash
    -   Filter berdasarkan status
    -   Notifikasi ketika status berhasil diubah
    -   Hanya menampilkan transaksi yang tidak cancelled

### 2. Pages untuk WashStatusResource

-   **ListWashStatuses**: `app/Filament/Cashier/Resources/WashStatusResource/Pages/ListWashStatuses.php`
-   **EditWashStatus**: `app/Filament/Cashier/Resources/WashStatusResource/Pages/EditWashStatus.php`

### 3. Update FinisherQueueController

-   **File**: `app/Http/Controllers/FinisherQueueController.php`
-   **Perubahan**: Menghilangkan transaksi dengan status "completed" dari tampilan finisher queue
-   **Dampak**: Transaksi yang sudah selesai tidak akan muncul di tampilan finisher

### 4. Navigation Update

-   **File**: `app/Providers/Filament/CashierPanelProvider.php`
-   **Penambahan**: Menu "Wash Status" di navigation cashier panel

## Cara Kerja

1. **Cashier mengakses menu "Wash Status"** di dashboard cashier
2. **Melihat daftar transaksi** dengan status pending atau in progress
3. **Memilih transaksi** yang akan diubah statusnya
4. **Mengubah status** melalui dropdown pada halaman edit
5. **Notifikasi** akan muncul ketika status berhasil diubah
6. **Jika status diubah ke "completed"**, transaksi akan hilang dari tampilan finisher queue

## Keamanan

-   Hanya role cashier yang dapat mengakses fitur ini
-   Transaksi cancelled tidak ditampilkan
-   Perubahan status akan terekam dalam database

## Database

Tidak ada perubahan struktur database karena kolom `status` sudah tersedia di tabel `wash_transactions`.

## UI/UX

-   Interface menggunakan Filament dengan tampilan yang clean dan modern
-   Status ditampilkan dengan badge berwarna untuk memudahkan identifikasi
-   Form editing dengan section yang terorganisir dengan baik
-   Real-time notification untuk feedback user
