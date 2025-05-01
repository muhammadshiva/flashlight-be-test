<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserVehicle;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserVehicleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = UserVehicle::with(['user', 'vehicle']);

            // Filter by user_id if provided
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by vehicle_id if provided
            if ($request->has('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }

            $userVehicles = $query->get();
            return $this->successResponse($userVehicles, 'User vehicles retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getByUserId($userId)
    {
        try {
            $userVehicles = UserVehicle::with(['user', 'vehicle'])
                ->where('user_id', $userId)
                ->get();

            if ($userVehicles->isEmpty()) {
                return $this->errorResponse('No vehicles found for this user', 404);
            }

            return $this->successResponse($userVehicles, 'User vehicles retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getByVehicleId($vehicleId)
    {
        try {
            $userVehicles = UserVehicle::with(['user', 'vehicle'])
                ->where('vehicle_id', $vehicleId)
                ->get();

            if ($userVehicles->isEmpty()) {
                return $this->errorResponse('No users found for this vehicle', 404);
            }

            return $this->successResponse($userVehicles, 'User vehicles retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'vehicle_id' => 'required|exists:vehicles,id',
                'license_plate' => 'required|string|unique:user_vehicles,license_plate',
            ]);

            if ($validator->fails()) {
                // Check if the validation error is about license plate duplication
                if (
                    $validator->errors()->has('license_plate') &&
                    in_array('The license plate has already been taken.', $validator->errors()->get('license_plate'))
                ) {
                    $existingUserVehicle = UserVehicle::where('license_plate', $request->license_plate)->first();

                    if ($existingUserVehicle) {

                        $response = [
                            'id' => $existingUserVehicle->id,
                            'user_id' => $existingUserVehicle->user_id,
                            'vehicle_id' => $existingUserVehicle->vehicle_id,
                            'license_plate' => $existingUserVehicle->license_plate,
                            'created_at' => $existingUserVehicle->created_at,
                            'updated_at' => $existingUserVehicle->updated_at,
                        ];

                        return $this->successResponse($response, 'License plate already exists, returning existing user vehicle.', 201);
                    }
                }

                // Other validation errors
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                $userVehicle = UserVehicle::create($request->all());
                DB::commit();
                return $this->successResponse($userVehicle, 'User vehicle created successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }


    public function show(UserVehicle $userVehicle)
    {
        try {
            $userVehicle->load(['user', 'vehicle']);
            return $this->successResponse($userVehicle, 'User vehicle retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, UserVehicle $userVehicle)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'vehicle_id' => 'required|exists:vehicles,id',
                'license_plate' => 'required|string|unique:user_vehicles,license_plate,' . $userVehicle->id,
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                $userVehicle->update($request->all());
                DB::commit();
                return $this->successResponse($userVehicle, 'User vehicle updated successfully');
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(UserVehicle $userVehicle)
    {
        try {
            DB::beginTransaction();

            try {
                $userVehicle->delete();
                DB::commit();
                return $this->successResponse(null, 'User vehicle deleted successfully', 204);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
