# Finisher Queue Display Documentation

## Overview

Tampilan khusus untuk finisher/detailer yang menampilkan antrian cucian secara realtime. Tampilan ini terpisah dari sistem admin Filament dan dapat diakses melalui URL khusus.

## Access URL

**Production URL:** `https://your-domain.com/finisher/queue`
**Local Development:** `http://localhost:8000/finisher/queue`

## Features

### 📊 Real-time Dashboard

-   **Live Statistics Cards:**
    -   Pending (Yellow) - Transaksi menunggu
    -   In Progress (Blue) - Transaksi sedang dikerjakan
    -   Completed (Green) - Transaksi selesai
    -   Total Queue (Purple) - Total antrian

### 🔄 Auto-refresh

-   **Auto-refresh setiap 30 detik**
-   **Manual refresh button** dengan loading indicator
-   **Toggle auto-refresh** on/off
-   **Last updated timestamp**

### 📋 Queue Table

Kolom yang ditampilkan sesuai gambar:

1. **No** - Nomor urut
2. **Motorbike** - Nama kendaraan
3. **License Plate** - Plat nomor kendaraan
4. **Customer Name** - Nama pelanggan
5. **Additional Services** - Layanan tambahan (selain cuci utama)
6. **Food and Drinks** - Pesanan makanan/minuman
7. **Membership** - Status membership (dengan icon star)
8. **Total Amount** - Total biaya

### 🎨 Visual Design

-   **Dark theme** dengan gradien abu-abu
-   **Color-coded rows** berdasarkan status:
    -   Yellow tint untuk Pending
    -   Blue tint untuk In Progress
    -   Green tint untuk Completed
-   **Membership badge** dengan gradien emas dan icon star
-   **Responsive design** untuk berbagai ukuran layar

### 📱 Interactive Elements

-   **Hover effects** pada baris tabel
-   **Loading states** saat refresh
-   **Empty state** ketika tidak ada antrian
-   **Smooth transitions** untuk semua animasi

## Technical Implementation

### Backend

-   **Controller:** `FinisherQueueController`
-   **Routes:**
    -   `/finisher/queue` - Main display
    -   `/finisher/queue/data` - JSON API endpoint
-   **Model:** `WashTransaction` dengan relasi lengkap

### Frontend

-   **Framework:** Alpine.js untuk interactivity
-   **Styling:** Tailwind CSS
-   **Icons:** Font Awesome
-   **Fonts:** Inter (Google Fonts)

### Data Structure

```json
{
    "id": 1,
    "motorbike": "Nmax",
    "license_plate": "P 2345 VBF",
    "customer_name": "Danny",
    "additional_services": ["Compound", "Wax", "Black Again"],
    "food_drinks": ["Coffee (2)", "Fried Noodle (1)"],
    "membership": "Premium",
    "total_amount": 150000,
    "status": "pending",
    "wash_date": "2024-02-02 10:30:00"
}
```

## Usage Instructions

### For Finisher/Detailer:

1. **Akses URL:** `/finisher/queue`
2. **Monitor antrian** secara realtime
3. **Lihat detail** setiap transaksi
4. **Perhatikan membership** customer (star badge)
5. **Prioritaskan** berdasarkan status dan waktu

### For Management:

1. **Display pada monitor/TV** di area finishing
2. **Public access** - tidak perlu login
3. **Always-on display** untuk monitoring
4. **Auto-refresh** memastikan data selalu terkini

## Configuration

### Finisher Name

Edit di view `resources/views/finisher/queue.blade.php`:

```javascript
finisherName: 'Fulan Maulana', // Ganti nama sesuai kebutuhan
```

### Refresh Interval

Edit interval auto-refresh (default 30 detik):

```javascript
setInterval(() => {
    // ...
}, 30000); // 30000 = 30 detik
```

### Styling Customization

Edit CSS classes di bagian `<style>` untuk custom branding.

## API Endpoints

### GET /finisher/queue

**Response:** HTML page dengan tampilan queue

### GET /finisher/queue/data

**Response:** JSON array dengan data transaksi terbaru
**Content-Type:** application/json

## Browser Compatibility

-   ✅ Chrome/Edge (recommended)
-   ✅ Firefox
-   ✅ Safari
-   ✅ Mobile browsers

## Performance

-   **Lightweight:** Hanya load data yang diperlukan
-   **Efficient queries:** Dengan eager loading relasi
-   **Minimal JavaScript:** Alpine.js sangat ringan
-   **CDN resources:** Tailwind, Alpine, Font Awesome dari CDN

## Troubleshooting

### Data tidak muncul:

-   Pastikan ada transaksi dengan status pending/in_progress/completed
-   Check console browser untuk error JavaScript
-   Verify route `/finisher/queue/data` mengembalikan JSON

### Auto-refresh tidak bekerja:

-   Check checkbox "Auto Refresh" tercentang
-   Verify JavaScript tidak ada error
-   Check network connectivity

### Styling tidak sempurna:

-   Pastikan Tailwind CSS ter-load dari CDN
-   Check Font Awesome icons ter-load
-   Verify custom CSS tidak conflict

## Future Enhancements

1. **Sound notifications** untuk transaksi baru
2. **Filter by status**
3. **Priority indicators**
4. **Estimated completion time**
5. **Print queue summary**
6. **Multiple finisher support**
