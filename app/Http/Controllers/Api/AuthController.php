<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\DeviceFcmToken;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => 'customer', // Default type for registered users
            ]);

            // Create a customer record
            if ($user) {
                $user->customer()->create([
                    'is_active' => true,
                ]);
            }

            $accessToken = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            $user->load(['customer', 'staff', 'customer.membershipType']);

            DB::commit();
            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ], 'User registered successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
                'fcm_token' => ['nullable', 'string', 'min:50'], // Make FCM token optional with minimum length
                'device_id' => ['nullable', 'string'], // Device identifier (optional)
                'device_name' => ['nullable', 'string'],
                'platform' => ['nullable', 'string'],
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->errorResponse('The provided credentials are incorrect.', 401);
            }

            $user = User::where('email', $request->email)->first();

            // Store device FCM token only if both fcm_token and device_id are provided
            $deviceToken = null;
            if ($request->fcm_token && $request->device_id) {
                $deviceToken = DeviceFcmToken::storeDeviceToken(
                    $request->device_id,
                    $request->fcm_token,
                    $user->id,
                    $request->device_name,
                    $request->platform
                );
            }

            // Update user FCM token only if provided, for backward compatibility
            $updateData = ['last_login_at' => now()];
            if ($request->fcm_token) {
                $updateData['fcm_token'] = $request->fcm_token;
            }

            $user->update($updateData);

            $user->load(['customer', 'staff', 'customer.membershipType']);

            $accessToken = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            $responseData = [
                'user' => new UserResource($user),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'fcm_token_updated' => !empty($request->fcm_token),
                'device_token_stored' => !is_null($deviceToken),
            ];

            if ($request->device_id) {
                $responseData['device_id'] = $request->device_id;
            }

            $message = 'User logged in successfully';
            if (!empty($request->fcm_token) && !empty($request->device_id)) {
                $message .= ' and device FCM token stored';
            }

            return $this->successResponse($responseData, $message);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Login with email, password and FCM token (simplified endpoint)
     */
    public function loginWithFcm(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
                'fcm_token' => ['required', 'string', 'min:50'],
            ]);

            // Authenticate user
            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->errorResponse('Invalid email or password.', 401);
            }

            $user = User::where('email', $request->email)->first();

            // Update FCM token and last login time
            $user->update([
                'fcm_token' => $request->fcm_token,
                'last_login_at' => now(),
            ]);

            // Load relations
            $user->load(['customer', 'staff', 'customer.membershipType']);

            // Generate tokens
            $accessToken = $user->createToken('access_token')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $accessToken,
                'fcm_token' => $user->fcm_token,
                'message' => 'Login successful, FCM token updated for notifications',
            ], 'Login successful');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get current user's FCM token
     */
    public function getFcmToken(Request $request)
    {
        try {
            $user = $request->user();

            return $this->successResponse([
                'fcm_token' => $user->fcm_token,
                'has_fcm_token' => $user->hasFcmToken(),
                'last_login_at' => $user->last_login_at,
            ], 'FCM token retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => ['required', 'string'],
            ]);

            $user = $request->user();

            // Extract token ID from the refresh token
            $tokenParts = explode('|', $request->refresh_token);
            if (count($tokenParts) !== 2) {
                return response()->json(['error' => 'Invalid refresh token format'], 401);
            }

            $tokenId = $tokenParts[0];
            $tokenValue = $tokenParts[1];

            // Find the token by ID and name
            $oldToken = $user->tokens()
                ->where('id', $tokenId)
                ->where('name', 'refresh_token')
                ->first();

            if (!$oldToken) {
                return response()->json(['error' => 'Invalid refresh token'], 401);
            }

            // Delete the old refresh token
            $oldToken->delete();

            // Create new refresh token
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            return response()->json([
                'refresh_token' => $refreshToken
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            DB::beginTransaction();

            // Delete all tokens for the user
            $request->user()->tokens()->delete();

            DB::commit();
            return $this->successResponse(null, 'Logged out successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Device-specific logout that handles FCM tokens
     * POST /api/auth/device-logout
     */
    public function deviceLogout(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = $request->user();

            // Validate request
            $request->validate([
                'device_id' => ['nullable', 'string'],
            ]);

            // Get device ID from request or use null for current device only
            $deviceId = $request->device_id;

            // If device ID provided, mark that device's FCM token as inactive
            if ($deviceId) {
                $deviceToken = DeviceFcmToken::where('device_id', $deviceId)
                    ->where('is_active', true)
                    ->first();

                if ($deviceToken) {
                    $deviceToken->markAsInactive();
                }
            }

            // Revoke tokens for this device only (not all user's tokens)
            if ($request->bearerToken()) {
                // Extract token ID from the bearer token
                $tokenId = explode('|', $request->bearerToken())[0] ?? null;

                if ($tokenId) {
                    // Delete only the current token
                    $user->tokens()->where('id', $tokenId)->delete();
                }
            }

            DB::commit();
            return $this->successResponse(null, 'Device logged out successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
