# Modern FCM Integration Documentation

## Overview

Sistem notifikasi Firebase Cloud Messaging (FCM) menggunakan pendekatan modern dengan FCM v1 API, Laravel Notification system, dan Google API Client untuk Flashlight Car & Motorcycle Wash. Sistem ini telah di-refactor berdasarkan best practices dari artikel Medium untuk menggunakan Service Account authentication dan Laravel notification patterns.

## Modern Architecture Features

### 1. FCM Service with v1 API (`app/Services/FCMService.php`)

**Modern Features:**

-   ✅ **FCM v1 API** - Menggunakan `https://fcm.googleapis.com/v1/projects/{project-id}/messages:send`
-   ✅ **Service Account Authentication** - OAuth 2.0 dengan Google API Client
-   ✅ **Platform-specific Configuration** - Android dan iOS optimization
-   ✅ **Comprehensive Error Handling** - Proper error responses dan logging

**Key Methods:**

```php
// OAuth 2.0 token generation
protected function getAccessToken(): ?string

// Send FCM notification via v1 API
public function sendNotification(string $fcmToken, string $title, string $body, array $data = []): array

// Test FCM configuration dan authentication
public function testConnection(): array
```

### 2. Laravel Notification Channel (`app/Notifications/Channels/FcmChannel.php`)

**Features:**

-   ✅ **Custom Notification Channel** - Mengikuti pattern Laravel Notification
-   ✅ **Dependency Injection** - Proper service injection
-   ✅ **Token Validation** - FCM token availability check

```php
class FcmChannel
{
    public function send($notifiable, Notification $notification): array
}
```

### 3. FCM Notification Class (`app/Notifications/FcmNotification.php`)

**Features:**

-   ✅ **Laravel Notification Pattern** - Standard Laravel notification
-   ✅ **Queue Support** - Implements ShouldQueue interface
-   ✅ **Customizable Data** - Flexible notification data

```php
class FcmNotification extends Notification implements ShouldQueue
{
    public function via(object $notifiable): array
    public function toFcm(object $notifiable): array
}
```

### 4. User Model Integration (`app/Models/User.php`)

**FCM Methods:**

```php
// Route notifications for FCM channel
public function routeNotificationForFcm($notification = null): ?string

// Update FCM token
public function updateFcmToken(string $token): bool

// Check if user has FCM token
public function hasFcmToken(): bool
```

### 5. Token Management Controller (`app/Http/Controllers/FcmTokenController.php`)

**Endpoints:**

```php
PATCH /api/fcm/token    - Update FCM token
DELETE /api/fcm/token   - Remove FCM token
```

## Configuration

### 1. Environment Variables

```env
# Firebase Project Configuration
FIREBASE_PROJECT_ID=your-firebase-project-id
FCM_CREDENTIALS_PATH=storage/app/firebase/firebase-credentials.json

# Demo token untuk testing
FCM_DEMO_TOKEN=your-demo-fcm-token
```

### 2. Firebase Service Account

1. **Download Service Account Key:**

    - Go to Firebase Console > Project Settings > Service Accounts
    - Click "Generate new private key"
    - Save as `storage/app/firebase/firebase-credentials.json`

2. **Set Permissions:**
    ```bash
    chmod 600 storage/app/firebase/firebase-credentials.json
    ```

### 3. Services Configuration (`config/services.php`)

```php
'fcm' => [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'credentials_path' => env('FCM_CREDENTIALS_PATH', storage_path('app/firebase/firebase-credentials.json')),
    'demo_token' => env('FCM_DEMO_TOKEN'),
],
```

## Usage Examples

### 1. Using Laravel Notification System (Recommended)

```php
use App\Notifications\FcmNotification;

// Send to specific user
$user = User::find(1);
$user->notify(new FcmNotification(
    'Payment Completed',
    'Your payment has been processed successfully.',
    [
        'type' => 'payment_completed',
        'payment_id' => 123,
        'amount' => 50000,
    ]
));
```

### 2. Using FCM Service Directly

```php
use App\Services\FCMService;

$fcmService = new FCMService();
$result = $fcmService->sendNotification(
    $fcmToken,
    'Welcome!',
    'Thank you for joining us.',
    ['type' => 'welcome']
);
```

### 3. Payment Integration Example

```php
// In payment completion
$customer = $payment->washTransaction->customer;
if ($customer && $customer->user && $customer->user->hasFcmToken()) {
    $customer->user->notify(new FcmNotification(
        'Payment Completed Successfully',
        "Thank you {$customer->name}! Your payment has been processed.",
        [
            'type' => 'payment_completed',
            'payment_id' => $payment->id,
            'amount_paid' => $payment->amount_paid,
            'transaction_number' => $payment->washTransaction->transaction_number,
        ]
    ));
}
```

## Testing

### 1. Test Commands

```bash
# Test FCM configuration
php artisan fcm:test

# Test with specific token
php artisan fcm:test --token="your-fcm-token"

# Test with user ID (Laravel Notification)
php artisan fcm:test --user_id=1

# Custom message
php artisan fcm:test --token="token" --title="Custom Title" --body="Custom message"
```

### 2. API Testing

```bash
# Update FCM token
curl -X PATCH /api/fcm/token \
  -H "Authorization: Bearer your-api-token" \
  -H "Content-Type: application/json" \
  -d '{"fcm_token": "new-fcm-token"}'

# Send notification via API
curl -X POST /api/fcm/send \
  -H "Authorization: Bearer your-api-token" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "title": "Test Notification",
    "body": "This is a test message"
  }'
```

## Migration from Legacy FCM

### Changes Made:

1. **API Endpoint:** `fcm/send` → `fcm.googleapis.com/v1/projects/{project-id}/messages:send`
2. **Authentication:** Server Key → Service Account OAuth 2.0
3. **Payload Structure:** Legacy format → v1 API format
4. **Integration:** Direct service calls → Laravel Notification system

### Legacy vs Modern Comparison:

| Aspect         | Legacy               | Modern                    |
| -------------- | -------------------- | ------------------------- |
| API            | Legacy HTTP API      | FCM v1 API                |
| Auth           | Server Key           | Service Account OAuth 2.0 |
| Pattern        | Direct service calls | Laravel Notifications     |
| Error Handling | Basic                | Comprehensive             |
| Testing        | Limited              | Multiple test methods     |
| Security       | Server key in env    | Service account JSON      |

## Troubleshooting

### Common Issues:

1. **"FCM configuration is incomplete"**

    - Ensure `FIREBASE_PROJECT_ID` is set
    - Check `FCM_CREDENTIALS_PATH` points to valid JSON file

2. **"Failed to get access token"**

    - Verify service account JSON file exists and is readable
    - Check Firebase project permissions

3. **"No FCM token found"**

    - User hasn't registered FCM token
    - Use `/api/fcm/token` endpoint to update token

4. **"Invalid FCM token"**
    - Token might be expired or invalid
    - Regenerate token on client side

### Debug Steps:

1. Test configuration: `php artisan fcm:test`
2. Check logs: `tail -f storage/logs/laravel.log`
3. Verify credentials file: `cat storage/app/firebase/firebase-credentials.json`
4. Test token validity with Firebase Console

## Security Best Practices

1. **Service Account Security:**

    - Keep service account JSON file secure
    - Add to `.gitignore`
    - Use proper file permissions (600)

2. **Token Management:**

    - Validate FCM tokens before use
    - Handle token refresh on client side
    - Clean up invalid tokens

3. **API Security:**
    - Use authentication middleware for FCM endpoints
    - Validate input data
    - Rate limit notification endpoints

## Performance Optimization

1. **Queue Notifications:**

    - All notifications implement `ShouldQueue`
    - Use Redis or database queue for better performance

2. **Batch Processing:**

    - Group notifications when possible
    - Use FCM multicast for multiple recipients

3. **Error Handling:**
    - Graceful fallback for FCM failures
    - Log errors for monitoring

## Monitoring and Analytics

1. **Laravel Logs:**

    - FCM success/failure rates
    - Authentication issues
    - Token validation errors

2. **Firebase Console:**

    - Message delivery statistics
    - Device registration analytics
    - Campaign performance

3. **Custom Metrics:**
    - Payment notification success rate
    - User engagement metrics
    - Error tracking and alerting
