<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CustomerVehicle;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerVehicleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = CustomerVehicle::with(['customer', 'vehicle']);

            // Filter by customer_id if provided
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by vehicle_id if provided
            if ($request->has('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }

            $customerVehicles = $query->get();
            return $this->successResponse($customerVehicles, 'Customer vehicles retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getByCustomerId($customerId)
    {
        try {
            $customerVehicles = CustomerVehicle::with(['customer', 'vehicle'])
                ->where('customer_id', $customerId)
                ->get();

            if ($customerVehicles->isEmpty()) {
                return $this->errorResponse('No vehicles found for this customer', 404);
            }

            return $this->successResponse($customerVehicles, 'Customer vehicles retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getByVehicleId($vehicleId)
    {
        try {
            $customerVehicles = CustomerVehicle::with(['customer', 'vehicle'])
                ->where('vehicle_id', $vehicleId)
                ->get();

            if ($customerVehicles->isEmpty()) {
                return $this->errorResponse('No customers found for this vehicle', 404);
            }

            return $this->successResponse($customerVehicles, 'Customer vehicles retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'vehicle_id' => 'required|exists:vehicles,id',
                'license_plate' => 'required|string|unique:customer_vehicles,license_plate',
            ]);

            if ($validator->fails()) {
                // Check if the validation error is about license plate duplication
                if (
                    $validator->errors()->has('license_plate') &&
                    in_array('The license plate has already been taken.', $validator->errors()->get('license_plate'))
                ) {
                    $existingCustomerVehicle = CustomerVehicle::where('license_plate', $request->license_plate)->first();

                    if ($existingCustomerVehicle) {
                        $response = [
                            'id' => $existingCustomerVehicle->id,
                            'customer_id' => $existingCustomerVehicle->customer_id,
                            'vehicle_id' => $existingCustomerVehicle->vehicle_id,
                            'license_plate' => $existingCustomerVehicle->license_plate,
                            'created_at' => $existingCustomerVehicle->created_at,
                            'updated_at' => $existingCustomerVehicle->updated_at,
                        ];

                        return $this->successResponse($response, 'License plate already exists, returning existing customer vehicle.', 201);
                    }
                }

                // Other validation errors
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                $customerVehicle = CustomerVehicle::create($request->all());
                DB::commit();
                return $this->successResponse($customerVehicle, 'Customer vehicle created successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show(CustomerVehicle $customerVehicle)
    {
        try {
            $customerVehicle->load(['customer', 'vehicle']);
            return $this->successResponse($customerVehicle, 'Customer vehicle retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, CustomerVehicle $customerVehicle)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'vehicle_id' => 'required|exists:vehicles,id',
                'license_plate' => 'required|string|unique:customer_vehicles,license_plate,' . $customerVehicle->id,
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                $customerVehicle->update($request->all());
                DB::commit();
                return $this->successResponse($customerVehicle, 'Customer vehicle updated successfully');
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(CustomerVehicle $customerVehicle)
    {
        try {
            DB::beginTransaction();

            try {
                $customerVehicle->delete();
                DB::commit();
                return $this->successResponse(null, 'Customer vehicle deleted successfully', 204);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
