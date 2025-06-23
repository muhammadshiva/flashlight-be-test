# FCM Payment Notification Fix

## Masalah yang Ditemukan

Sistem tidak dapat mengirim FCM notification saat payment processing karena beberapa masalah:

### 1. FCM Token Tidak Valid/Kadaluarsa

-   **Penyebab**: Token FCM menjadi invalid karena:
    -   Aplikasi mobile di-uninstall dan install ulang
    -   Token expired karena tidak digunakan lama
    -   Device token berubah
-   **Gejala**: Log menunjukkan error `"UNREGISTERED"` dari Firebase
-   **Dampak**: Notification tidak terkirim ke customer

### 2. Error Reporting yang Misleading

-   **Penyebab**: Sistem melaporkan "notification sent successfully" padahal sebenarnya gagal
-   **Gejala**: Admin panel menampilkan success message meskipun FCM gagal
-   **Dampak**: Admin tidak menyadari ada masalah dengan notification

### 3. Tidak Ada Handling untuk Invalid Token

-   **Penyebab**: Sistem tidak menangani token yang invalid dengan baik
-   **Gejala**: Token invalid tetap tersimpan di database
-   **Dampak**: Sistem terus mencoba mengirim ke token yang sudah tidak valid

## Perbaikan yang Dilakukan

### 1. Improved FCM Service Error Handling

**File**: `app/Services/FCMService.php`

```php
// Menambahkan handling khusus untuk berbagai error code FCM
switch ($errorCode) {
    case 'UNREGISTERED':
        $errorMessage = 'FCM token is invalid or unregistered. The app may have been uninstalled or the token expired.';
        break;
    case 'INVALID_ARGUMENT':
        $errorMessage = 'Invalid FCM request parameters.';
        break;
    case 'SENDER_ID_MISMATCH':
        $errorMessage = 'FCM token does not match the sender ID.';
        break;
    case 'QUOTA_EXCEEDED':
        $errorMessage = 'FCM quota exceeded.';
        break;
    default:
        $errorMessage = 'FCM error: ' . $errorCode;
}
```

### 2. Auto-Clear Invalid Tokens

**File**: `app/Notifications/Channels/FcmChannel.php`

```php
// Automatically clear invalid tokens from user records
if (!$result['success'] && isset($result['error_code']) && $result['error_code'] === 'UNREGISTERED') {
    Log::info('FCM Channel: Clearing invalid token from user', [
        'user_id' => $notifiable->id,
        'token_prefix' => substr($fcmToken, 0, 20) . '...',
    ]);

    if (method_exists($notifiable, 'clearFcmToken')) {
        $notifiable->clearFcmToken();
    }
}
```

### 3. Enhanced User Model

**File**: `app/Models/User.php`

```php
/**
 * Clear FCM token (useful when token becomes invalid)
 */
public function clearFcmToken(): bool
{
    return $this->update(['fcm_token' => null]);
}
```

### 4. Better Payment Processing Error Handling

**Files**:

-   `app/Filament/Cashier/Resources/PaymentResource.php`
-   `app/Filament/Cashier/Resources/QRISPaymentResource.php`

```php
// Send notification and check result
try {
    $result = $customerUser->notify($notification);

    // Check if user still has FCM token after notification attempt
    $customerUser->refresh();
    if ($customerUser->hasFcmToken()) {
        // Success notification
    } else {
        // Warning notification - token was cleared due to invalidity
    }
} catch (\Exception $notificationException) {
    // Error handling
}
```

### 5. FCM Token Cleanup Command

**File**: `app/Console/Commands/CleanInvalidFcmTokensCommand.php`

```bash
# Check which tokens are invalid (dry run)
php artisan fcm:clean-invalid-tokens --dry-run

# Actually clean invalid tokens
php artisan fcm:clean-invalid-tokens
```

## Hasil Perbaikan

### Sebelum Perbaikan:

-   FCM notification gagal terkirim
-   Error tidak terdeteksi dengan baik
-   Token invalid tetap tersimpan
-   Admin tidak menyadari ada masalah

### Setelah Perbaikan:

-   ✅ Invalid token otomatis di-clear dari database
-   ✅ Error handling yang lebih baik dan informatif
-   ✅ Admin panel menampilkan status yang akurat
-   ✅ Command untuk membersihkan token invalid secara batch
-   ✅ Logging yang lebih detail untuk debugging

## Cara Menggunakan

### 1. Test FCM Notification

```bash
# Test dengan user tertentu
php artisan fcm:test --user_id=1

# Test payment notification
php artisan fcm:test --payment-test --user_id=1
```

### 2. Membersihkan Token Invalid

```bash
# Cek token mana yang invalid (tidak benar-benar menghapus)
php artisan fcm:clean-invalid-tokens --dry-run

# Benar-benar membersihkan token invalid
php artisan fcm:clean-invalid-tokens
```

### 3. Monitoring

-   Check Laravel logs di `storage/logs/laravel.log`
-   Monitor FCM notification status di admin panel
-   Warning notifications akan muncul jika FCM gagal

## Rekomendasi

1. **Regular Cleanup**: Jalankan `php artisan fcm:clean-invalid-tokens` secara berkala
2. **Monitor Logs**: Periksa logs secara rutin untuk error FCM
3. **User Education**: Inform users to update their FCM tokens jika mengalami masalah notification
4. **Automated Cleanup**: Pertimbangkan untuk menambahkan cleanup ke scheduled tasks

## Testing

Setelah perbaikan, sistem telah ditest dengan:

-   ✅ Valid FCM tokens - notification berhasil terkirim
-   ✅ Invalid FCM tokens - automatic cleanup dan warning notification
-   ✅ Payment processing - accurate status reporting
-   ✅ Error handling - proper error messages and logging
