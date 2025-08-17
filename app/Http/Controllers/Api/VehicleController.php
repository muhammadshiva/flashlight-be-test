<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $vehicles = Vehicle::get();
            return $this->successResponse($vehicles, 'Vehicles retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'brand' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'vehicle_type' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                $vehicle = Vehicle::create($request->all());
                DB::commit();
                return $this->successResponse($vehicle, 'Vehicle created successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show(Vehicle $vehicle)
    {
        try {
            $vehicle->load('user', 'washTransactions');
            return $this->successResponse($vehicle, 'Vehicle retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        try {
            $validator = Validator::make($request->all(), [
                'brand' => 'sometimes|required|string|max:255',
                'model' => 'sometimes|required|string|max:255',
                'color' => 'sometimes|nullable|string|max:50',
                'vehicle_type' => 'sometimes|required|string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                $vehicle->update($request->only(['brand', 'model', 'color', 'vehicle_type']));
                DB::commit();
                return $this->successResponse($vehicle, 'Vehicle updated successfully');
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(Vehicle $vehicle)
    {
        try {
            DB::beginTransaction();

            try {
                $vehicle->delete();
                DB::commit();
                return $this->successResponse(null, 'Vehicle deleted successfully', 204);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
