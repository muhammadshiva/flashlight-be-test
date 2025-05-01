<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $users = User::all();
            return $this->successResponse($users, 'Users retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users',
                'phone_number' => 'required|string|unique:users',
                'password' => 'nullable|string|min:6',
                'address' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'country' => 'nullable|string',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'membership_type_id' => 'nullable|exists:membership_types,id',
                'membership_expires_at' => 'nullable|date',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                // Check if the error is about the phone number being taken
                if (
                    $validator->errors()->has('phone_number') &&
                    in_array('The phone number has already been taken.', $validator->errors()->get('phone_number'))
                ) {
                    $existingUser = User::where('phone_number', $request->phone_number)->first();

                    if ($existingUser) {
                        $response = [
                            'id' => $existingUser->id,
                            'name' => $existingUser->name,
                            'phone_number' => $existingUser->phone_number,
                            'updated_at' => $existingUser->updated_at,
                            'created_at' => $existingUser->created_at,
                        ];
                        return $this->successResponse($response, 'Phone number already exists, returning existing user.', 201);
                    }
                }

                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $data = $request->all();
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('users', 'public');
                $data['profile_image'] = $path;
            }

            $user = User::create($data);

            DB::commit();

            $response = [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
                'updated_at' => $user->updated_at,
                'created_at' => $user->created_at,
            ];
            return $this->successResponse($response, 'User fetched successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return $this->handleException($e);
        }
    }


    public function show(User $user)
    {
        try {
            return $this->successResponse($user, 'User retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'phone_number' => 'sometimes|required|string|unique:users,phone_number,' . $user->id,
                'password' => 'nullable|string|min:6',
                'address' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'country' => 'nullable|string',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'membership_type_id' => 'nullable|exists:membership_types,id',
                'membership_expires_at' => 'nullable|date',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $data = $request->all();
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $oldImage = $user->profile_image;
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('users', 'public');
                $data['profile_image'] = $path;
            }

            $user->update($data);

            if ($request->hasFile('profile_image') && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            DB::commit();
            return $this->successResponse($user, 'User updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return $this->handleException($e);
        }
    }

    public function destroy(User $user)
    {
        try {
            DB::beginTransaction();

            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $user->delete();

            DB::commit();
            return $this->successResponse(null, 'User deleted successfully', 204);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function profile()
    {
        try {
            $user = Auth::user();
            return $this->successResponse($user, 'Profile retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'phone_number' => 'sometimes|required|string|unique:users,phone_number,' . $user->id,
                'address' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'country' => 'nullable|string',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $data = $request->all();
            $oldImage = $user->profile_image;

            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('users', 'public');
                $data['profile_image'] = $path;
            }

            $user->update($data);

            if ($request->hasFile('profile_image') && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            DB::commit();
            return $this->successResponse($user, 'Profile updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return $this->handleException($e);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6',
                'new_password_confirmation' => 'required|same:new_password'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect', 422);
            }

            DB::beginTransaction();

            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            DB::commit();
            return $this->successResponse(null, 'Password updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
