<?php

namespace App\Notifications\Channels;

use App\Services\FCMService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): array
    {
        try {
            Log::info('FCM Channel: Starting notification send', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
                'notification_type' => get_class($notification),
            ]);

            $fcmToken = $notifiable->routeNotificationFor('fcm', $notification);

            if (!$fcmToken) {
                Log::warning('FCM Channel: No FCM token found', [
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->id ?? null,
                    'fcm_token' => $notifiable->fcm_token ?? 'not set',
                ]);

                return [
                    'success' => false,
                    'message' => 'No FCM token found for this user',
                    'error' => 'User does not have an FCM token',
                ];
            }

            Log::info('FCM Channel: Token found, preparing notification', [
                'token_prefix' => substr($fcmToken, 0, 20) . '...',
            ]);

            if (!method_exists($notification, 'toFcm')) {
                Log::error('FCM Channel: Notification does not have toFcm method', [
                    'notification_type' => get_class($notification),
                ]);

                return [
                    'success' => false,
                    'message' => 'Notification does not support FCM',
                    'error' => 'Notification class must implement toFcm method',
                ];
            }

            /** @var \App\Notifications\FcmNotification $notification */
            $fcmData = $notification->toFcm($notifiable);

            Log::info('FCM Channel: Sending notification via FCMService', [
                'title' => $fcmData['title'],
                'body' => substr($fcmData['body'], 0, 100) . (strlen($fcmData['body']) > 100 ? '...' : ''),
                'data_keys' => array_keys($fcmData['data'] ?? []),
            ]);

            $result = $this->fcmService->sendNotification(
                $fcmToken,
                $fcmData['title'],
                $fcmData['body'],
                $fcmData['data'] ?? []
            );

            // Handle unregistered tokens by clearing them from the user
            if (!$result['success'] && isset($result['error_code']) && $result['error_code'] === 'UNREGISTERED') {
                Log::info('FCM Channel: Clearing invalid token from user', [
                    'user_id' => $notifiable->id,
                    'token_prefix' => substr($fcmToken, 0, 20) . '...',
                ]);

                if (method_exists($notifiable, 'clearFcmToken')) {
                    $notifiable->clearFcmToken();
                }
            }

            Log::info('FCM Channel: Notification send result', [
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'No message',
                'error_code' => $result['error_code'] ?? null,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('FCM Channel: Exception during notification send', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred in FCM channel',
                'error' => $e->getMessage(),
            ];
        }
    }
}
