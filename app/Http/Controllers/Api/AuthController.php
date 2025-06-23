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
                'fcm_token' => ['required', 'string', 'min:50'], // Make FCM token required with minimum length
                'device_id' => ['required', 'string'], // Device identifier
                'device_name' => ['nullable', 'string'],
                'platform' => ['nullable', 'string'],
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->errorResponse('The provided credentials are incorrect.', 401);
            }

            $user = User::where('email', $request->email)->first();

            // Store device FCM token (this will be used for all transactions)
            $deviceToken = DeviceFcmToken::storeDeviceToken(
                $request->device_id,
                $request->fcm_token,
                $user->id,
                $request->device_name,
                $request->platform
            );

            // Still update user FCM token for backward compatibility
            $user->update([
                'fcm_token' => $request->fcm_token,
                'last_login_at' => now(),
            ]);

            $user->load(['customer', 'staff', 'customer.membershipType']);

            $accessToken = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'fcm_token_updated' => true,
                'device_token_stored' => true,
                'device_id' => $request->device_id,
            ], 'User logged in successfully and device FCM token stored');
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
}
