<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WashTransaction;
use App\Models\Product;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkOrderController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of work orders
     */
    public function index(Request $request)
    {
        try {
            $query = WorkOrder::with([
                'customer.user',
                'customerVehicle.vehicle',
                'products',
                'washTransactions.posTransaction',
                'posTransaction'
            ]);

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range if provided
            if ($request->has('date_from')) {
                $query->whereDate('order_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('order_date', '<=', $request->date_to);
            }

            // Filter by customer if provided
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            $workOrders = $query->latest('order_date')->get();

            // Format the response
            $workOrders->transform(function ($workOrder) {
                $workOrder->total_price = (float) $workOrder->total_price;
                $workOrder->has_payment = $workOrder->hasPayment();
                return $workOrder;
            });

            return $this->successResponse($workOrders, 'Work orders retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created work order
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'customer_vehicle_id' => 'required|exists:customer_vehicles,id',
                'order_date' => 'required|date',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string',
                'special_instructions' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                // Generate order number and queue number
                $orderNumber = WorkOrder::generateOrderNumber();
                $queueNumber = WorkOrder::generateQueueNumber();

                // Calculate total price
                $totalPrice = 0;
                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    $totalPrice += $product->price * $productData['quantity'];
                }

                // Create work order
                $workOrder = WorkOrder::create([
                    'order_number' => $orderNumber,
                    'customer_id' => $request->customer_id,
                    'customer_vehicle_id' => $request->customer_vehicle_id,
                    'total_price' => $totalPrice,
                    'order_date' => $request->order_date,
                    'status' => WorkOrder::STATUS_PENDING,
                    'notes' => $request->notes,
                    'special_instructions' => $request->special_instructions,
                    'queue_number' => $queueNumber,
                ]);

                // Attach products with their quantities and prices
                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    $subtotal = $product->price * $productData['quantity'];

                    $workOrder->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'price' => $product->price,
                        'subtotal' => $subtotal,
                    ]);
                }

                DB::commit();

                $workOrder->load([
                    'customer.user',
                    'customerVehicle.vehicle',
                    'products'
                ]);

                // Format the response
                $workOrder->total_price = (float) $workOrder->total_price;
                $workOrder->has_payment = false;

                return $this->successResponse([
                    'work_order' => $workOrder,
                    'order_number' => $workOrder->order_number,
                    'queue_number' => $workOrder->queue_number,
                ], 'Work order created successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified work order
     */
    public function show(WorkOrder $workOrder)
    {
        try {
            $workOrder->load([
                'customer.user',
                'customerVehicle.vehicle',
                'products',
                'posTransaction'
            ]);

            // Format total_price as float
            $workOrder->total_price = (float) $workOrder->total_price;
            $workOrder->has_payment = $workOrder->hasPayment();

            return $this->successResponse($workOrder, 'Work order retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified work order
     */
    public function update(Request $request, WorkOrder $workOrder)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'sometimes|required|exists:customers,id',
                'customer_vehicle_id' => 'sometimes|required|exists:customer_vehicles,id',
                'order_date' => 'sometimes|required|date',
                'status' => 'sometimes|required|in:pending,confirmed,in_progress,ready_for_pickup,completed,cancelled',
                'products' => 'sometimes|required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string',
                'special_instructions' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                // Store old customer ID for updating counts
                $oldCustomerId = $workOrder->customer_id;

                // Update basic work order details
                $updateData = $request->only([
                    'customer_id',
                    'customer_vehicle_id',
                    'order_date',
                    'status',
                    'notes',
                    'special_instructions',
                ]);

                // Set timestamp based on status change
                if ($request->has('status')) {
                    switch ($request->status) {
                        case WorkOrder::STATUS_CONFIRMED:
                            $updateData['confirmed_at'] = now();
                            break;
                        case WorkOrder::STATUS_IN_PROGRESS:
                            $updateData['started_at'] = now();
                            break;
                        case WorkOrder::STATUS_COMPLETED:
                            $updateData['completed_at'] = now();
                            break;
                    }
                }

                $workOrder->update($updateData);

                // If products are being updated
                if ($request->has('products')) {
                    // Calculate new total price
                    $totalPrice = 0;
                    foreach ($request->products as $productData) {
                        $product = Product::findOrFail($productData['product_id']);
                        $totalPrice += $product->price * $productData['quantity'];
                    }

                    // Update total price
                    $workOrder->update(['total_price' => $totalPrice]);

                    // Detach all existing products
                    $workOrder->products()->detach();

                    // Attach new products
                    foreach ($request->products as $productData) {
                        $product = Product::findOrFail($productData['product_id']);
                        $subtotal = $product->price * $productData['quantity'];

                        $workOrder->products()->attach($product->id, [
                            'quantity' => $productData['quantity'],
                            'price' => $product->price,
                            'subtotal' => $subtotal,
                        ]);
                    }
                }

                // Update transaction counts for both old and new customer if customer changed
                if ($oldCustomerId !== $workOrder->customer_id) {
                    $oldCustomer = Customer::find($oldCustomerId);
                    if ($oldCustomer) {
                        $oldCustomer->updateTransactionCounts();
                    }
                }
                $workOrder->customer->updateTransactionCounts();

                DB::commit();

                return $this->successResponse(
                    $workOrder->load([
                        'customer.user',
                        'customerVehicle.vehicle',
                        'products',
                        'posTransaction'
                    ]),
                    'Work order updated successfully'
                );
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified work order
     */
    public function destroy(WorkOrder $workOrder)
    {
        try {
            // Check if work order has been paid
            if ($workOrder->hasPayment()) {
                return $this->errorResponse('Cannot delete work order that has been paid', 400);
            }

            DB::beginTransaction();

            try {
                $customer = $workOrder->customer;
                $workOrder->delete();

                // Update customer transaction counts
                $customer->updateTransactionCounts();

                DB::commit();
                return $this->successResponse(null, 'Work order deleted successfully', 204);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Confirm work order and create wash transaction
     */
    public function confirm(WorkOrder $workOrder)
    {
        try {
            if ($workOrder->isConfirmed()) {
                return $this->errorResponse('Work order is already confirmed', 400);
            }

            if ($workOrder->isCancelled()) {
                return $this->errorResponse('Cannot confirm a cancelled work order', 400);
            }

            DB::beginTransaction();

            try {
                // Confirm work order and create wash transaction
                $washTransaction = $workOrder->confirmAndCreateWashTransaction();

                DB::commit();

                return $this->successResponse([
                    'work_order' => $workOrder->load([
                        'customer.user',
                        'customerVehicle.vehicle',
                        'products',
                        'washTransactions.posTransaction'
                    ]),
                    'wash_transaction' => $washTransaction->load([
                        'customer.user',
                        'customerVehicle.vehicle',
                        'products'
                    ])
                ], 'Work order confirmed and wash transaction created successfully');
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Cancel work order
     */
    public function cancel(WorkOrder $workOrder)
    {
        try {
            if ($workOrder->isCancelled()) {
                return $this->errorResponse('Work order is already cancelled', 400);
            }

            if ($workOrder->isCompleted()) {
                return $this->errorResponse('Cannot cancel a completed work order', 400);
            }

            if ($workOrder->hasPayment()) {
                return $this->errorResponse('Cannot cancel work order that has been paid', 400);
            }

            $workOrder->update(['status' => WorkOrder::STATUS_CANCELLED]);

            return $this->successResponse(
                $workOrder->load([
                    'customer.user',
                    'customerVehicle.vehicle',
                    'products',
                    'posTransaction'
                ]),
                'Work order cancelled successfully'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get work orders by customer
     */
    public function getByCustomerId($customerId)
    {
        try {
            $workOrders = WorkOrder::with([
                'customer.user',
                'customerVehicle.vehicle',
                'products',
                'posTransaction'
            ])
                ->where('customer_id', $customerId)
                ->latest('order_date')
                ->get();

            // Format the response
            $workOrders->transform(function ($workOrder) {
                $workOrder->total_price = (float) $workOrder->total_price;
                $workOrder->has_payment = $workOrder->hasPayment();
                return $workOrder;
            });

            return $this->successResponse($workOrders, 'Customer work orders retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get queue information
     */
    public function getQueue()
    {
        try {
            $today = now()->format('Y-m-d');

            $queue = WorkOrder::with([
                'customer.user',
                'customerVehicle.vehicle'
            ])
                ->whereDate('order_date', $today)
                ->whereIn('status', [
                    WorkOrder::STATUS_CONFIRMED,
                    WorkOrder::STATUS_IN_PROGRESS,
                    WorkOrder::STATUS_READY_FOR_PICKUP
                ])
                ->orderBy('queue_number')
                ->get();

            return $this->successResponse([
                'queue' => $queue,
                'total_queue' => $queue->count(),
                'date' => $today
            ], 'Queue information retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
