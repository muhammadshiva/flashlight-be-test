<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    /**
     * Update the user's FCM token.
     */
    public function updateToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $user->updateFcmToken($request->fcm_token);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FCM token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the user's FCM token.
     */
    public function removeToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->update(['fcm_token' => null]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove FCM token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
