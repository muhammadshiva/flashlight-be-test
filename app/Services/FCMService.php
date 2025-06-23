<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\WashTransaction;
use App\Models\Payment;
use App\Models\DeviceFcmToken;

class FCMService
{
    protected ?string $projectId = null;
    protected ?string $credentialsPath = null;
    protected string $fcmUrl;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id') ?: env('FIREBASE_PROJECT_ID');
        $this->credentialsPath = config('services.fcm.credentials_path') ?: env('FCM_CREDENTIALS_PATH');

        // Convert relative path to absolute path if needed
        if ($this->credentialsPath && !file_exists($this->credentialsPath) && !str_starts_with($this->credentialsPath, '/')) {
            $this->credentialsPath = base_path($this->credentialsPath);
        }

        $this->fcmUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        if (empty($this->projectId) || empty($this->credentialsPath)) {
            Log::warning('FCM Project ID or Credentials Path is not configured. FCM notifications will be disabled.');
        }
    }

    /**
     * Prepare data payload for FCM (all values must be strings)
     */
    protected function prepareDataPayload(array $data): array
    {
        $preparedData = [];

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $preparedData[$key] = json_encode($value);
            } else {
                $preparedData[$key] = (string) $value;
            }
        }

        return $preparedData;
    }

    /**
     * Get access token using service account
     */
    protected function getAccessToken(): ?string
    {
        try {
            $client = new GoogleClient();

            // Ensure credentials path is absolute
            $credentialsPath = $this->credentialsPath;
            if (!str_starts_with($credentialsPath, '/')) {
                $credentialsPath = base_path($credentialsPath);
            }

            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();

            return $token['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to get FCM access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send FCM notification using v1 API
     */
    public function sendNotification(string $fcmToken, string $title, string $body, array $data = []): array
    {
        try {
            // Check if configuration is valid
            if (empty($this->projectId) || empty($this->credentialsPath)) {
                return [
                    'success' => false,
                    'message' => 'FCM configuration is incomplete',
                    'error' => 'Please set FIREBASE_PROJECT_ID and FCM_CREDENTIALS_PATH in your .env file',
                ];
            }

            // Ensure credentials path is absolute
            $credentialsPath = $this->credentialsPath;
            if (!str_starts_with($credentialsPath, '/')) {
                $credentialsPath = base_path($credentialsPath);
            }

            // Check if credentials file exists
            if (!file_exists($credentialsPath)) {
                return [
                    'success' => false,
                    'message' => 'FCM credentials file not found',
                    'error' => 'Please ensure the Firebase service account JSON file exists at: ' . $credentialsPath,
                ];
            }

            // Get access token
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get access token',
                    'error' => 'Could not authenticate with Firebase',
                ];
            }

            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $this->prepareDataPayload(array_merge([
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'timestamp' => now()->toISOString(),
                    ], $data)),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'default_sound' => true,
                        ],
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            $responseData = $response->json();

            // Log the response for monitoring
            Log::info('FCM notification sent', [
                'title' => $title,
                'body' => $body,
                'token' => substr($fcmToken, 0, 20) . '...',
                'response' => $responseData,
                'status_code' => $response->status(),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Notification sent successfully',
                    'data' => $responseData,
                ];
            } else {
                // Handle specific FCM errors
                $errorMessage = 'Failed to send notification';
                $errorCode = null;

                if (isset($responseData['error'])) {
                    $error = $responseData['error'];
                    if (isset($error['details'][0]['errorCode'])) {
                        $errorCode = $error['details'][0]['errorCode'];
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
                    }
                }

                Log::warning('FCM notification failed', [
                    'title' => $title,
                    'token' => substr($fcmToken, 0, 20) . '...',
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'full_response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $responseData['error'] ?? $responseData,
                    'error_code' => $errorCode,
                    'status_code' => $response->status(),
                    'full_response' => $responseData,
                ];
            }
        } catch (\Exception $e) {
            Log::error('FCM notification failed', [
                'error' => $e->getMessage(),
                'title' => $title,
                'body' => $body,
                'token' => substr($fcmToken, 0, 20) . '...',
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred while sending notification',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send multiple notifications
     */
    public function sendMultipleNotifications(array $tokens, string $title, string $body, array $data = []): array
    {
        $results = [];

        foreach ($tokens as $token) {
            $results[] = $this->sendNotification($token, $title, $body, $data);
        }

        return $results;
    }

    /**
     * Send payment completion notification
     */
    public function sendPaymentNotification(Payment $payment): array
    {
        $transaction = $payment->washTransaction;
        $customer = $transaction->customer;

        $title = 'Payment Processed Successfully';
        $body = "Payment of IDR " . number_format($payment->amount_paid) . " for " . $customer->name . "'s vehicle wash has been processed.";

        $data = [
            'type' => 'payment_completed',
            'payment_id' => $payment->id,
            'transaction_id' => $transaction->id,
            'payment_method' => $payment->method,
            'amount' => $payment->amount_paid,
            'customer_name' => $customer->name,
            'transaction_number' => $transaction->transaction_number,
        ];

        // For demo purposes, using the provided token
        // In production, you should store FCM tokens in database for each user/device
        $fcmToken = config('services.fcm.demo_token', 'dAh-b6NORSm2cgmSb-txSQ:APA91bHe-uJhnyJmtrb7qD2LG3QTo1xYRUINfLivrFEyS7bQ7Elox_Yyz7t5CKJFOU48DEsj0bOSH7fabD0gxIa0jYt-_0g3esZX3QHeWF-mDpZ8o_f7_gY');

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }

    /**
     * Send QRIS payment initiated notification
     */
    public function sendQRISInitiatedNotification(Payment $payment): array
    {
        $transaction = $payment->washTransaction;
        $customer = $transaction->customer;

        $title = 'QRIS Payment Initiated';
        $body = "QRIS payment for " . $customer->name . "'s vehicle wash is waiting for completion. Amount: IDR " . number_format($payment->amount_paid);

        $data = [
            'type' => 'qris_initiated',
            'payment_id' => $payment->id,
            'transaction_id' => $transaction->id,
            'qris_transaction_id' => $payment->qris_transaction_id,
            'amount' => $payment->amount_paid,
            'customer_name' => $customer->name,
            'transaction_number' => $transaction->transaction_number,
        ];

        $fcmToken = config('services.fcm.demo_token', 'dAh-b6NORSm2cgmSb-txSQ:APA91bHe-uJhnyJmtrb7qD2LG3QTo1xYRUINfLivrFEyS7bQ7Elox_Yyz7t5CKJFOU48DEsj0bOSH7fabD0gxIa0jYt-_0g3esZX3QHeWF-mDpZ8o_f7_gY');

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }

    /**
     * Test FCM connection and configuration
     */
    public function testConnection(): array
    {
        try {
            // Check if configuration is valid
            if (empty($this->projectId) || empty($this->credentialsPath)) {
                return [
                    'success' => false,
                    'message' => 'FCM configuration is incomplete',
                    'error' => 'Please set FIREBASE_PROJECT_ID and FCM_CREDENTIALS_PATH in your .env file',
                ];
            }

            // Ensure credentials path is absolute
            $credentialsPath = $this->credentialsPath;
            if (!str_starts_with($credentialsPath, '/')) {
                $credentialsPath = base_path($credentialsPath);
            }

            // Check if credentials file exists
            if (!file_exists($credentialsPath)) {
                return [
                    'success' => false,
                    'message' => 'FCM credentials file not found',
                    'error' => 'Please ensure the Firebase service account JSON file exists at: ' . $credentialsPath,
                ];
            }

            // Get access token
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get access token',
                    'error' => 'Could not authenticate with Firebase',
                ];
            }

            return [
                'success' => true,
                'message' => 'FCM configuration is valid',
                'data' => [
                    'project_id' => $this->projectId,
                    'credentials_path' => $credentialsPath,
                    'access_token_length' => strlen($accessToken),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception occurred during connection test',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find FCM token for a customer with fallback mechanisms
     */
    public function findCustomerFcmToken($customer, $customerId = null): ?array
    {
        try {
            Log::info('FCM Token Search: Starting search for customer', [
                'customer_provided' => $customer !== null,
                'customer_id' => $customerId ?? ($customer ? $customer->id : null),
            ]);

            $tokenInfo = null;

            // Method 1: Direct customer -> user relationship
            if ($customer && $customer->user && $customer->user->hasFcmToken()) {
                $tokenInfo = [
                    'user' => $customer->user,
                    'token' => $customer->user->fcm_token,
                    'method' => 'customer_user_direct',
                    'user_id' => $customer->user->id,
                    'user_name' => $customer->user->name,
                ];

                Log::info('FCM Token Search: Found via direct customer->user relationship', [
                    'user_id' => $tokenInfo['user_id'],
                    'user_name' => $tokenInfo['user_name'],
                ]);

                return $tokenInfo;
            }

            // Method 2: Search by customer_id in users table (if customer has FCM token directly)
            if ($customerId || ($customer && $customer->id)) {
                $searchCustomerId = $customerId ?? $customer->id;

                $userWithToken = \App\Models\User::where('type', 'customer')
                    ->whereNotNull('fcm_token')
                    ->whereHas('customer', function ($query) use ($searchCustomerId) {
                        $query->where('id', $searchCustomerId);
                    })
                    ->first();

                if ($userWithToken) {
                    $tokenInfo = [
                        'user' => $userWithToken,
                        'token' => $userWithToken->fcm_token,
                        'method' => 'customer_id_search',
                        'user_id' => $userWithToken->id,
                        'user_name' => $userWithToken->name,
                    ];

                    Log::info('FCM Token Search: Found via customer ID search', [
                        'user_id' => $tokenInfo['user_id'],
                        'user_name' => $tokenInfo['user_name'],
                        'customer_id' => $searchCustomerId,
                    ]);

                    return $tokenInfo;
                }
            }

            // Method 3: Fallback - find any customer user with FCM token (for testing/demo)
            $anyCustomerUser = \App\Models\User::where('type', 'customer')
                ->whereNotNull('fcm_token')
                ->first();

            if ($anyCustomerUser) {
                $tokenInfo = [
                    'user' => $anyCustomerUser,
                    'token' => $anyCustomerUser->fcm_token,
                    'method' => 'fallback_any_customer',
                    'user_id' => $anyCustomerUser->id,
                    'user_name' => $anyCustomerUser->name,
                ];

                Log::warning('FCM Token Search: Using fallback - any customer with token', [
                    'original_customer_id' => $customerId ?? ($customer ? $customer->id : null),
                    'fallback_user_id' => $tokenInfo['user_id'],
                    'fallback_user_name' => $tokenInfo['user_name'],
                ]);

                return $tokenInfo;
            }

            // No token found
            Log::warning('FCM Token Search: No FCM token found for customer', [
                'customer_id' => $customerId ?? ($customer ? $customer->id : null),
                'customer_user_exists' => $customer && $customer->user ? true : false,
                'customer_user_id' => $customer && $customer->user ? $customer->user->id : null,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('FCM Token Search: Exception occurred', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId ?? ($customer ? $customer->id : null),
            ]);

            return null;
        }
    }

    /**
     * Get device FCM token (newest approach - 1 device for multiple users)
     */
    public function getDeviceFcmToken(?string $deviceId = null): ?string
    {
        try {
            Log::info('FCM Device Token Search: Starting search', [
                'device_id_provided' => $deviceId !== null,
                'device_id' => $deviceId,
            ]);

            // If specific device ID provided, get token for that device
            if ($deviceId) {
                $deviceToken = DeviceFcmToken::getActiveTokenForDevice($deviceId);
                if ($deviceToken) {
                    Log::info('FCM Device Token Search: Found token for specific device', [
                        'device_id' => $deviceId,
                        'token_preview' => substr($deviceToken, 0, 20) . '...',
                    ]);
                    return $deviceToken;
                }
            }

            // Fallback: Get the most recent active device token
            $latestToken = DeviceFcmToken::getLatestActiveToken();
            if ($latestToken) {
                Log::info('FCM Device Token Search: Using latest active device token', [
                    'token_preview' => substr($latestToken, 0, 20) . '...',
                ]);
                return $latestToken;
            }

            Log::warning('FCM Device Token Search: No active device token found', [
                'device_id' => $deviceId,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('FCM Device Token Search: Exception occurred', [
                'error' => $e->getMessage(),
                'device_id' => $deviceId,
            ]);
            return null;
        }
    }

    /**
     * Send payment notification using device FCM token
     */
    public function sendPaymentNotificationToDevice(Payment $payment, ?string $deviceId = null): array
    {
        $fcmToken = $this->getDeviceFcmToken($deviceId);

        if (!$fcmToken) {
            return [
                'success' => false,
                'message' => 'No active device FCM token found',
                'error' => 'Please ensure at least one user has logged in from mobile device with FCM token',
            ];
        }

        $transaction = $payment->washTransaction;
        $customer = $transaction->customer;

        $title = 'Payment Completed';
        $body = "Payment completed for {$customer->name}. {$payment->method} payment of IDR " . number_format($payment->amount_paid) . " has been processed successfully.";

        $data = [
            'type' => 'payment_completed',
            'payment_id' => $payment->id,
            'transaction_id' => $transaction->id,
            'payment_method' => $payment->method,
            'amount' => $payment->amount_paid,
            'customer_name' => $customer->name,
            'transaction_number' => $transaction->transaction_number,
        ];

        Log::info('FCM Payment Notification: Sending to device', [
            'payment_id' => $payment->id,
            'customer_name' => $customer->name,
            'amount' => $payment->amount_paid,
            'method' => $payment->method,
            'token_preview' => substr($fcmToken, 0, 20) . '...',
        ]);

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }
}
