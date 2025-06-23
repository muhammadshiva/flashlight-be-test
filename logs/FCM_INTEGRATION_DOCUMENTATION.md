# FCM Integration Documentation

## Overview

Sistem notifikasi Firebase Cloud Messaging (FCM) yang terintegrasi dengan payment processing pada Flashlight Car & Motorcycle Wash. Sistem ini mengirim notifikasi push real-time ke perangkat mobile saat pembayaran berhasil diproses.

## Features Implemented

### 1. FCM Service (`app/Services/FCMService.php`)

**Fitur Utama:**

-   Send individual FCM notifications via HTTP request using Laravel HTTP Client
-   Send multiple notifications to multiple tokens
-   Payment-specific notification templates
-   Comprehensive error handling and logging
-   Test connection functionality

**Key Methods:**

-   `sendNotification()` - Send single FCM notification
-   `sendMultipleNotifications()` - Send to multiple devices
-   `sendPaymentNotification()` - Payment completion notification
-   `sendQRISInitiatedNotification()` - QRIS payment initiated notification
-   `testConnection()` - Test FCM configuration

### 2. FCM Controller (`app/Http/Controllers/FCMController.php`)

**API Endpoints:**

-   `POST /api/fcm/send` - Send single notification
-   `POST /api/fcm/send-multiple` - Send to multiple tokens
-   `GET /api/fcm/test` - Test FCM connection
-   `GET /api/fcm/config` - Get configuration status

**Request Validation:**

-   Token validation (required)
-   Title and body validation (max length)
-   Data payload validation (optional array)

### 3. Configuration (`config/services.php`)

```php
'fcm' => [
    'server_key' => env('FCM_SERVER_KEY'),
    'demo_token' => env('FCM_DEMO_TOKEN', 'default_token_here'),
],
```

### 4. Payment Integration

**Cash Payment Flow:**

1. User processes cash payment via cashier panel
2. Payment record created with status 'completed'
3. FCM notification sent to configured token
4. Success/failure message displayed to cashier

**QRIS Payment Flow:**

1. User initiates QRIS payment
2. QR code generated and payment record created with status 'pending'
3. FCM notification sent for payment initiation
4. When payment completed (via check status), completion notification sent

## Environment Configuration

Add these variables to your `.env` file:

```env
# FCM Configuration
FCM_SERVER_KEY=your_firebase_server_key_here
FCM_DEMO_TOKEN=dAh-b6NORSm2cgmSb-txSQ:APA91bHe-uJhnyJmtrb7qD2LG3QTo1xYRUINfLivrFEyS7bQ7Elox_Yyz7t5CKJFOU48DEsj0bOSH7fabD0gxIa0jYt-_0g3esZX3QHeWF-mDpZ8o_f7_gY
```

## Getting Firebase Server Key

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Go to Project Settings > Cloud Messaging tab
4. Copy the "Server key" value
5. Add it to your `.env` file as `FCM_SERVER_KEY`

## Testing FCM Integration

### 1. Via Artisan Command

```bash
# Test with default token and message
php artisan fcm:test

# Test with custom token
php artisan fcm:test --token=your_fcm_token_here

# Test with custom message
php artisan fcm:test --title="Custom Title" --body="Custom message body"
```

### 2. Via API Endpoints

**Test Connection:**

```bash
curl -X GET http://your-app.com/api/fcm/test \
  -H "Authorization: Bearer your_api_token"
```

**Send Test Notification:**

```bash
curl -X POST http://your-app.com/api/fcm/send \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your_api_token" \
  -d '{
    "token": "your_fcm_token",
    "title": "Test Notification",
    "body": "This is a test message",
    "data": {
      "type": "test",
      "custom_field": "custom_value"
    }
  }'
```

**Check Configuration:**

```bash
curl -X GET http://your-app.com/api/fcm/config \
  -H "Authorization: Bearer your_api_token"
```

### 3. Via Payment Processing

1. Login to cashier panel (`/cashier`)
2. Go to "Payment Processing"
3. Click "Pay Now" on any transaction
4. Complete payment (Cash or QRIS)
5. Check device for push notification

## Notification Payload Structure

### Payment Completion Notification

```json
{
    "to": "fcm_token_here",
    "notification": {
        "title": "Payment Processed Successfully",
        "body": "Payment of IDR 50,000 for John Doe's vehicle wash has been processed.",
        "sound": "default",
        "badge": "1"
    },
    "data": {
        "click_action": "FLUTTER_NOTIFICATION_CLICK",
        "timestamp": "2024-01-15T10:30:00.000Z",
        "type": "payment_completed",
        "payment_id": "123",
        "transaction_id": "456",
        "payment_method": "cash",
        "amount": "50000",
        "customer_name": "John Doe",
        "transaction_number": "TRX-20240115-ABC123"
    },
    "priority": "high",
    "content_available": true
}
```

### QRIS Initiated Notification

```json
{
    "to": "fcm_token_here",
    "notification": {
        "title": "QRIS Payment Initiated",
        "body": "QRIS payment for John Doe's vehicle wash is waiting for completion. Amount: IDR 50,000",
        "sound": "default",
        "badge": "1"
    },
    "data": {
        "click_action": "FLUTTER_NOTIFICATION_CLICK",
        "timestamp": "2024-01-15T10:30:00.000Z",
        "type": "qris_initiated",
        "payment_id": "123",
        "transaction_id": "456",
        "qris_transaction_id": "QRIS-1705312200-ABC123",
        "amount": "50000",
        "customer_name": "John Doe",
        "transaction_number": "TRX-20240115-ABC123"
    },
    "priority": "high",
    "content_available": true
}
```

## Error Handling

### Service Level Error Handling

1. **Configuration Errors**: Server key not configured
2. **Network Errors**: HTTP request failures
3. **FCM API Errors**: Invalid tokens, quota exceeded, etc.
4. **General Exceptions**: Unexpected errors

### User Interface Error Handling

-   Success notifications show when FCM notification sent successfully
-   Warning notifications show when payment succeeds but FCM fails
-   Error details logged to Laravel log files
-   Graceful degradation - payment processing continues even if FCM fails

## Logging

All FCM activities are logged to Laravel's default log channel:

-   Successful notifications with response data
-   Failed notifications with error details
-   Token information (first 20 characters for security)
-   Response status codes and timing

## Security Considerations

1. **Server Key Protection**: Store FCM server key in environment variables
2. **Token Handling**: Log only partial tokens for debugging
3. **API Authentication**: All FCM endpoints require authentication
4. **Input Validation**: Validate all input parameters
5. **Error Information**: Don't expose sensitive error details to end users

## Mobile App Integration

For the mobile app to receive these notifications:

1. Initialize FCM in your Flutter/React Native app
2. Request notification permissions
3. Generate FCM token on app startup
4. Send token to your backend for storage (future enhancement)
5. Handle notification taps and background processing

## Future Enhancements

1. **User-Specific Tokens**: Store FCM tokens per user in database
2. **Notification Preferences**: Allow users to customize notification types
3. **Push Notification History**: Store notification history for tracking
4. **Rich Notifications**: Add images, action buttons, etc.
5. **Topic-Based Messaging**: Group notifications by topics (payments, promotions, etc.)
6. **Scheduled Notifications**: Send notifications at specific times
7. **Analytics**: Track notification delivery and engagement rates

## Troubleshooting

### Common Issues

1. **"FCM Server Key is not configured"**

    - Add `FCM_SERVER_KEY` to your `.env` file
    - Restart your application

2. **"Invalid registration token"**

    - Check if the FCM token is valid and not expired
    - Regenerate token on mobile app

3. **"Authentication Error"**

    - Verify your Firebase server key is correct
    - Check Firebase project settings

4. **Notifications not received**
    - Check mobile app FCM implementation
    - Verify token is correct
    - Check device network connection
    - Verify app is not in battery optimization mode

### Debug Commands

```bash
# Test FCM service
php artisan fcm:test

# Check Laravel logs
tail -f storage/logs/laravel.log

# Clear Laravel cache
php artisan config:clear
php artisan cache:clear
```

## API Documentation

### Send Notification

**Endpoint:** `POST /api/fcm/send`

**Headers:**

-   `Content-Type: application/json`
-   `Authorization: Bearer {token}`

**Request Body:**

```json
{
    "token": "string (required)",
    "title": "string (required, max: 255)",
    "body": "string (required, max: 500)",
    "data": "object (optional)"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Notification sent successfully",
  "data": {
    "multicast_id": 123456789,
    "success": 1,
    "failure": 0,
    "results": [...]
  }
}
```

### Send Multiple Notifications

**Endpoint:** `POST /api/fcm/send-multiple`

**Request Body:**

```json
{
    "tokens": ["string1", "string2"],
    "title": "string (required)",
    "body": "string (required)",
    "data": "object (optional)"
}
```

**Response:**

```json
{
  "success": true,
  "message": "2 out of 2 notifications sent successfully",
  "results": [...],
  "summary": {
    "total": 2,
    "success": 2,
    "failed": 0
  }
}
```

---

## Conclusion

FCM integration sekarang sudah terintegrasi penuh dengan sistem pembayaran Flashlight Wash. Sistem ini memberikan notifikasi real-time yang reliable dengan error handling yang comprehensive dan logging yang detail untuk monitoring dan debugging.
