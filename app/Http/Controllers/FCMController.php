<?php

namespace App\Http\Controllers;

use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FCMController extends Controller
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send FCM notification
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->fcmService->sendNotification(
            $request->token,
            $request->title,
            $request->body,
            $request->data ?? []
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Send notification to multiple tokens
     */
    public function sendMultipleNotifications(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tokens' => 'required|array|min:1',
            'tokens.*' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = $this->fcmService->sendMultipleNotifications(
            $request->tokens,
            $request->title,
            $request->body,
            $request->data ?? []
        );

        $successCount = count(array_filter($results, fn($result) => $result['success']));
        $totalCount = count($results);

        return response()->json([
            'success' => $successCount > 0,
            'message' => "$successCount out of $totalCount notifications sent successfully",
            'results' => $results,
            'summary' => [
                'total' => $totalCount,
                'success' => $successCount,
                'failed' => $totalCount - $successCount,
            ],
        ]);
    }

    /**
     * Test FCM connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $result = $this->fcmService->testConnection();
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get FCM configuration status
     */
    public function getConfigStatus(): JsonResponse
    {
        try {
            $serverKey = config('services.fcm.server_key', env('FCM_SERVER_KEY'));
            $demoToken = config('services.fcm.demo_token');

            return response()->json([
                'success' => true,
                'message' => 'FCM configuration status',
                'data' => [
                    'server_key_configured' => !empty($serverKey),
                    'demo_token_configured' => !empty($demoToken),
                    'fcm_url' => 'https://fcm.googleapis.com/fcm/send',
                    'last_checked' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get configuration status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
