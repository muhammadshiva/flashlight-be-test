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

            // Ambil data customer dari entri pertama (karena relasinya sama)
            $customer = $customerVehicles->first()->customer;

            // Format data customer
            $customerData = [
                'id' => $customer->id,
                'user_id' => $customer->user_id,
                'address' => $customer->address,
                'membership_type_id' => $customer->membership_type_id,
                'membership_status' => $customer->membership_status,
                'membership_expires_at' => $customer->membership_expires_at,
                'is_active' => $customer->is_active,
                'last_login_at' => $customer->last_login_at,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
                'deleted_at' => $customer->deleted_at,
                'total_transactions' => $customer->total_transactions,
                'total_premium_transactions' => $customer->total_premium_transactions,
                'total_discount_approvals' => $customer->total_discount_approvals,
            ];

            // Format data vehicles
            $vehicles = $customerVehicles->map(function ($cv) {
                return [
                    'id' => $cv->id,
                    'license_plate' => $cv->license_plate,
                    'vehicle' => [
                        'id' => $cv->vehicle->id,
                        'brand' => $cv->vehicle->brand,
                        'model' => $cv->vehicle->model,
                        'vehicle_type' => $cv->vehicle->vehicle_type,
                    ]
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Customer vehicles retrieved successfully',
                'data' => [
                    'customer' => $customerData,
                    'vehicles' => $vehicles,
                ],
            ]);
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

    public function getByLicensePlate($licensePlate)
    {
        try {
            $customerVehicle = CustomerVehicle::with('vehicle')
                ->where('license_plate', $licensePlate)
                ->first();

            if (!$customerVehicle) {
                return $this->errorResponse('No customer vehicle found for this license plate', 404);
            }

            // Ambil dan bentuk ulang data secara manual
            $responseData = [
                'id' => $customerVehicle->id,
                'customer_id' => $customerVehicle->customer_id,
                'license_plate' => $customerVehicle->license_plate,
                'vehicle_id' => $customerVehicle->vehicle_id,
                'vehicle' => $customerVehicle->vehicle,
            ];

            return $this->successResponse($responseData, 'Customer vehicle retrieved successfully');
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
                    $existingCustomerVehicle = CustomerVehicle::where('license_plate', $request->license_plate)
                        ->with(['customer', 'vehicle'])
                        ->first();

                    if ($existingCustomerVehicle) {
                        $response = [
                            'id' => $existingCustomerVehicle->id,
                            'customer' => $existingCustomerVehicle->customer,
                            'vehicle' => $existingCustomerVehicle->vehicle,
                            'license_plate' => $existingCustomerVehicle->license_plate,
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
                $customerVehicle->load(['customer', 'vehicle']);

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
