<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\Staff;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $users = User::with(['customer', 'staff'])->get();
            return $this->successResponse($users, 'Users retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Create a new user
     *
     * Example JSON request:
     * {
     *     "name": "John Doe",
     *     "email": "john@example.com",  // optional
     *     "phone_number": "+1234567890", // optional
     *     "password": "password123",     // optional
     *     "password_confirmation": "password123", // required if password is provided
     *     "type": "customer",            // optional, can be: admin, customer, staff
     *     "profile_image": "file"        // optional, image file
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:users',
                'phone_number' => 'nullable|string|max:20',
                'password' => ['nullable', 'confirmed', Password::defaults()],
                'type' => 'required|in:admin,customer,staff',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                $userData = $request->only(['name', 'email', 'phone_number', 'type']);
                if ($request->has('password')) {
                    $userData['password'] = Hash::make($request->password);
                }

                if ($request->hasFile('profile_image')) {
                    $userData['profile_image'] = $request->file('profile_image')->store('profile-images', 'public');
                }

                $user = User::create($userData);

                // Create related record based on user type
                if ($request->type === 'customer') {
                    $this->createCustomer($user->id, $request);
                } elseif ($request->type === 'staff') {
                    $this->createStaff($user->id, $request);
                }

                DB::commit();
                return $this->successResponse($user->load(['customer', 'staff']), 'User created successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                if (isset($userData['profile_image'])) {
                    Storage::disk('public')->delete($userData['profile_image']);
                }
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with(['customer', 'staff'])->find($id);

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
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
                'phone_number' => 'nullable|string|max:20',
                'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $userData = $request->only(['name', 'email', 'phone_number']);

            if ($request->has('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('profile_image')) {
                $userData['profile_image'] = $request->file('profile_image')->store('profile-images', 'public');
            }

            $user->update($userData);

            // Update related record if it exists
            if ($user->isCustomer()) {
                $this->updateCustomer($user->customer, $request);
            } elseif ($user->isStaff()) {
                $this->updateStaff($user->staff, $request);
            }

            return $this->successResponse($user->load(['customer', 'staff']), 'User updated successfully');
        } catch (Exception $e) {
            if (isset($userData['profile_image'])) {
                Storage::disk('public')->delete($userData['profile_image']);
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

    private function createCustomer($userId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'membership_type_id' => 'nullable|exists:membership_types,id',
            'membership_expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $customerData = $request->only([
            'address',
            'city',
            'state',
            'postal_code',
            'country',
            'membership_type_id',
            'membership_expires_at'
        ]);
        $customerData['user_id'] = $userId;

        return Customer::create($customerData);
    }

    private function createStaff($userId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'position' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'hire_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staffData = $request->only(['position', 'salary', 'hire_date', 'notes']);
        $staffData['user_id'] = $userId;
        $staffData['is_active'] = true;

        return Staff::create($staffData);
    }

    private function updateCustomer(Customer $customer, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'membership_type_id' => 'nullable|exists:membership_types,id',
            'membership_expires_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $customer->update($request->only([
            'address',
            'city',
            'state',
            'postal_code',
            'country',
            'membership_type_id',
            'membership_expires_at',
            'is_active'
        ]));
    }

    private function updateStaff(Staff $staff, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'position' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'hire_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staff->update($request->only([
            'position',
            'salary',
            'hire_date',
            'notes',
            'is_active'
        ]));
    }
}
