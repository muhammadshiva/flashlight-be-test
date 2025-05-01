<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            ]);

            $accessToken = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            DB::commit();
            return $this->successResponse([
                'user' => $user,
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
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->errorResponse('The provided credentials are incorrect.', 401);
            }

            $user = User::where('email', $request->email)->first();
            $accessToken = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ], 'User logged in successfully');
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
