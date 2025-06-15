# Bluetooth Thermal Printer Troubleshooting Guide

## Panduan Mengatasi Masalah Koneksi Printer Thermal Bluetooth

### Masalah Umum dan Solusi

#### 1. Tidak Ada Device Yang Terdeteksi Saat Scan

**Gejala:**

-   Tombol "Scan Devices" tidak menemukan printer
-   Pesan "No Thermal Printers Found"

**Solusi:**

1. **Pastikan printer hidup dan dalam mode pairing**

    ```
    - Nyalakan printer thermal
    - Aktifkan mode Bluetooth pairing (biasanya tombol Bluetooth 3-5 detik)
    - LED Bluetooth harus berkedip atau menyala biru
    ```

2. **Periksa Bluetooth sistem**

    ```bash
    # Untuk macOS
    system_profiler SPBluetoothDataType

    # Untuk Linux
    bluetoothctl
    > scan on
    > devices
    ```

3. **Restart layanan Bluetooth**

    ```bash
    # macOS
    sudo launchctl unload /System/Library/LaunchDaemons/com.apple.blued.plist
    sudo launchctl load /System/Library/LaunchDaemons/com.apple.blued.plist

    # Linux
    sudo systemctl restart bluetooth
    ```

#### 2. Device Terdeteksi Tapi Gagal Connect

**Gejala:**

-   Printer muncul di daftar saat scan
-   Pesan "Connection Failed" saat connect
-   Status tetap "Disconnected"

**Solusi:**

1. **Unpair dan pair ulang device**

    ```bash
    # macOS
    blueutil --unpair [MAC_ADDRESS]
    blueutil --pair [MAC_ADDRESS]

    # Linux
    bluetoothctl
    > remove [MAC_ADDRESS]
    > pair [MAC_ADDRESS]
    > trust [MAC_ADDRESS]
    ```

2. **Periksa jarak dan interferensi**

    - Pastikan printer dalam jarak 5-10 meter
    - Hindari gangguan WiFi, microwave, atau device lain
    - Coba pindahkan ke lokasi dengan interferensi minimal

3. **Reset printer ke factory settings**
    - Lihat manual printer untuk cara factory reset
    - Biasanya: matikan printer, tahan tombol feed + power 10 detik

#### 3. Connect Berhasil Tapi Print Gagal

**Gejala:**

-   Status connection "Connected"
-   Test connection berhasil
-   Print receipt gagal atau tidak keluar

**Solusi:**

1. **Periksa kertas dan hardware**

    ```
    - Pastikan ada kertas dalam printer
    - Periksa ukuran kertas sesuai setting (58mm/80mm)
    - Bersihkan head printer dengan alkohol
    ```

2. **Verifikasi setting printer**

    - Buka Printer Settings
    - Pastikan Paper Width sesuai dengan kertas
    - Test dengan width berbeda (58mm ↔ 80mm)

3. **Check driver dan compatibility**
    - Beberapa printer butuh driver khusus
    - Coba dengan aplikasi printer lain untuk test

### Instalasi Requirements

#### macOS Requirements

1. **Install blueutil (optional tapi recommended)**

    ```bash
    brew install blueutil
    ```

2. **Enable Bluetooth dan pairing**

    ```bash
    # Check Bluetooth status
    blueutil -p

    # Turn on Bluetooth if off
    blueutil -p 1
    ```

#### Linux Requirements

1. **Install BlueZ tools**

    ```bash
    # Ubuntu/Debian
    sudo apt-get install bluez bluez-tools

    # CentOS/RHEL
    sudo yum install bluez bluez-tools
    ```

2. **Start Bluetooth service**
    ```bash
    sudo systemctl enable bluetooth
    sudo systemctl start bluetooth
    ```

### Diagnostic Tools

#### 1. Menggunakan Diagnostics Button

Klik tombol "Diagnostics" di cashier panel untuk melihat:

-   OS information
-   Selected device
-   Connection status
-   Connection attempts
-   Saved settings

#### 2. Manual Debugging

```bash
# Check sistem Bluetooth
sudo dmesg | grep -i bluetooth

# Check paired devices
bluetoothctl paired-devices

# Check system logs
tail -f /var/log/syslog | grep -i bluetooth
```

#### 3. Laravel Logs

```bash
# Check application logs
tail -f storage/logs/laravel.log

# Filter printer logs only
grep -i "printer\|bluetooth" storage/logs/laravel.log
```

### Printer Compatibility

#### Tested & Working Printers

-   ✅ Xprinter XP-58IIH
-   ✅ Xprinter XP-80IIH
-   ✅ Epson TM-T20III
-   ✅ Generic 58mm Bluetooth Thermal Printer

#### Known Issues

-   ❌ Beberapa printer China generic butuh driver khusus
-   ❌ Windows support belum fully implemented
-   ❌ Printer dengan encryption khusus mungkin tidak kompatibel

### Configuration Options

#### Environment Variables

```env
# .env file settings
PRINTER_SCAN_TIMEOUT=10
PRINTER_MAX_CONNECTION_ATTEMPTS=3
PRINTER_CONNECTION_RETRY_DELAY=2
PRINTER_AUTO_PRINT=true
PRINTER_DETAILED_LOGGING=true
PRINTER_MOCK_ENABLED=false
```

#### Session Settings

```php
// Printer settings stored in session
session(['printer_settings' => [
    'printer_device' => 'device_id',
    'auto_print' => true,
    'printer_width' => 58
]]);
```

### Advanced Troubleshooting

#### 1. Connection Timeout Issues

Jika connection sering timeout:

```php
// Increase timeout in config/printer.php
'bluetooth' => [
    'max_connection_attempts' => 5,
    'connection_retry_delay' => 3,
]
```

#### 2. Multiple Cashier Stations

Untuk multiple kasir dengan printer berbeda:

```php
// Set unique session per cashier
session(['cashier_id' => auth()->user()->id]);
session(["printer_settings_{cashier_id}" => $settings]);
```

#### 3. Printer Auto-Recovery

Implement auto-reconnect:

```php
// Add to ThermalPrinterService
public function autoReconnect(): bool
{
    if (!$this->isConnected() && !empty($this->printerDevice)) {
        return $this->connectToPrinter($this->printerDevice);
    }
    return true;
}
```

### Emergency Procedures

#### Jika Printer Sama Sekali Tidak Berfungsi

1. **Enable Mock Mode**

    ```env
    PRINTER_MOCK_ENABLED=true
    ```

    Ini akan memungkinkan sistem tetap berjalan tanpa printer fisik.

2. **Manual Receipt Printing**

    - Print receipt ke file PDF
    - Email receipt ke customer
    - Print menggunakan printer biasa

3. **Alternative Printer Setup**
    - Gunakan network printer sebagai backup
    - Setup printer sharing dari komputer lain
    - Gunakan USB thermal printer sebagai alternatif

### Contact & Support

Jika masalah masih berlanjut:

1. Check logs di `storage/logs/laravel.log`
2. Gunakan Diagnostics button untuk info lengkap
3. Screenshot error messages
4. Note model printer dan OS yang digunakan

### Update & Maintenance

#### Regular Maintenance

-   Clean printer head setiap minggu
-   Update Bluetooth drivers
-   Check paper roll sebelum shift
-   Test connection setiap hari

#### Software Updates

-   Monitor Laravel logs untuk error patterns
-   Update printer config jika ada printer baru
-   Backup printer settings sebelum update sistem
