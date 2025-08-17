<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WashTransaction;
use App\Models\WorkOrder;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Shift;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WashTransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = WashTransaction::with([
                'workOrder',
                'customer.user',
                'customerVehicle',
                'primaryProduct',
                'products',
                'user',
                'shift',
                'posTransaction'
            ]);

            // Filter by shift_id if provided
            if ($request->has('shift_id')) {
                $query->where('shift_id', $request->shift_id);
            }

            $transactions = $query->latest()->get();

            // Format the response to ensure total_price is float and add total_premium_transactions
            $transactions->transform(function ($transaction) {
                $transaction->total_price = (float) $transaction->total_price;
                $transaction->total_premium_transactions = $transaction->customer->total_premium_transactions;
                return $transaction;
            });

            return $this->successResponse($transactions, 'Wash transactions retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'customer_vehicle_id' => 'required|exists:customer_vehicles,id',
                'product_id' => 'nullable|exists:products,id',
                'user_id' => 'required|exists:users,id',
                'payment_method' => 'required|in:cash,cashless,transfer',
                'wash_date' => 'required|date',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                // Generate transaction number
                $today = now()->format('Ymd'); // example: 20250523

                // Get the last transaction number for today
                $lastTransaction = WashTransaction::whereDate('created_at', now())
                    ->orderBy('created_at', 'desc')
                    ->first();

                $sequence = 1;
                if ($lastTransaction) {
                    // Extract the sequence number from the last transaction
                    if (preg_match('/TRX-\d{8}-(\d{3})/', $lastTransaction->transaction_number, $matches)) {
                        $sequence = (int)$matches[1] + 1;
                    }
                }

                $transactionNumber = sprintf('TRX-%s-%03d', $today, $sequence);

                // Calculate total price
                $totalPrice = 0;
                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    $totalPrice += $product->price * $productData['quantity'];
                }

                // Get active shift for the user
                $activeShift = Shift::getActiveShift($request->user_id);

                // Create wash transaction
                $transaction = WashTransaction::create([
                    'transaction_number' => $transactionNumber,
                    'customer_id' => $request->customer_id,
                    'customer_vehicle_id' => $request->customer_vehicle_id,
                    'product_id' => $request->product_id,
                    'user_id' => $request->user_id,
                    'shift_id' => $activeShift?->id,
                    'total_price' => $totalPrice,
                    'payment_method' => $request->payment_method,
                    'wash_date' => $request->wash_date,
                    'status' => WashTransaction::STATUS_PENDING,
                    'notes' => $request->notes,
                ]);

                // Attach products with their quantities and prices
                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    $subtotal = $product->price * $productData['quantity'];

                    $transaction->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'price' => $product->price,
                        'subtotal' => $subtotal,
                    ]);
                }

                // Update customer transaction counts
                $transaction->customer->updateTransactionCounts();

                DB::commit();
                $transaction->load(['customer.user', 'customerVehicle', 'primaryProduct', 'products', 'user']);

                // Format the response
                $transaction->total_price = (float) $transaction->total_price;
                $transaction->total_premium_transactions = $transaction->customer->total_premium_transactions;

                return $this->successResponse([
                    'transaction' => $transaction,
                    'transaction_number' => $transaction->transaction_number,
                    'date' => now()->format('Y-m-d'),
                    'sequence' => $sequence
                ], 'Wash transaction created successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show(WashTransaction $washTransaction)
    {
        try {
            $washTransaction->load([
                'customer.user',
                'customerVehicle',
                'primaryProduct',
                'products',
                'user'
            ]);

            // Format total_price as float and add total_premium_transactions
            $washTransaction->total_price = (float) $washTransaction->total_price;
            $washTransaction->total_premium_transactions = $washTransaction->customer->total_premium_transactions;

            return $this->successResponse($washTransaction, 'Wash transaction retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getByCustomerId($customerId)
    {
        try {
            $transactions = WashTransaction::with([
                'customer.user',
                'customerVehicle',
                'primaryProduct',
                'products',
                'user'
            ])
                ->where('customer_id', $customerId)
                ->latest()
                ->get();

            // Format the response to ensure total_price is float and add total_premium_transactions
            $transactions->transform(function ($transaction) {
                $transaction->total_price = (float) $transaction->total_price;
                $transaction->total_premium_transactions = $transaction->customer->total_premium_transactions;
                return $transaction;
            });

            return $this->successResponse($transactions, 'Customer wash transactions retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, WashTransaction $washTransaction)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'sometimes|required|exists:customers,id',
                'customer_vehicle_id' => 'sometimes|required|exists:customer_vehicles,id',
                'product_id' => 'nullable|exists:products,id',
                'user_id' => 'sometimes|required|exists:users,id',
                'payment_method' => 'sometimes|required|in:cash,cashless,transfer',
                'wash_date' => 'sometimes|required|date',
                'status' => 'sometimes|required|in:pending,in_progress,completed,cancelled',
                'products' => 'sometimes|required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                // Store old customer ID for updating counts
                $oldCustomerId = $washTransaction->customer_id;

                // Update basic transaction details
                $washTransaction->update($request->only([
                    'customer_id',
                    'customer_vehicle_id',
                    'product_id',
                    'user_id',
                    'payment_method',
                    'wash_date',
                    'status',
                    'notes',
                ]));

                // If products are being updated
                if ($request->has('products')) {
                    // Calculate new total price
                    $totalPrice = 0;
                    foreach ($request->products as $productData) {
                        $product = Product::findOrFail($productData['product_id']);
                        $totalPrice += $product->price * $productData['quantity'];
                    }

                    // Update total price
                    $washTransaction->update(['total_price' => $totalPrice]);

                    // Detach all existing products
                    $washTransaction->products()->detach();

                    // Attach new products
                    foreach ($request->products as $productData) {
                        $product = Product::findOrFail($productData['product_id']);
                        $subtotal = $product->price * $productData['quantity'];

                        $washTransaction->products()->attach($product->id, [
                            'quantity' => $productData['quantity'],
                            'price' => $product->price,
                            'subtotal' => $subtotal,
                        ]);
                    }
                }

                // Update transaction counts for both old and new customer if customer changed
                if ($oldCustomerId !== $washTransaction->customer_id) {
                    $oldCustomer = Customer::find($oldCustomerId);
                    if ($oldCustomer) {
                        $oldCustomer->updateTransactionCounts();
                    }
                }
                $washTransaction->customer->updateTransactionCounts();

                DB::commit();
                return $this->successResponse(
                    $washTransaction->load(['customer.user', 'customerVehicle', 'primaryProduct', 'products', 'user']),
                    'Wash transaction updated successfully'
                );
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(WashTransaction $washTransaction)
    {
        try {
            DB::beginTransaction();

            try {
                $customer = $washTransaction->customer;
                $washTransaction->delete();

                // Update customer transaction counts
                $customer->updateTransactionCounts();

                DB::commit();
                return $this->successResponse(null, 'Wash transaction deleted successfully', 204);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function complete(WashTransaction $washTransaction)
    {
        try {
            if ($washTransaction->isCompleted()) {
                return $this->errorResponse('Wash transaction is already completed', 400);
            }

            $washTransaction->update(['status' => WashTransaction::STATUS_COMPLETED]);
            return $this->successResponse(
                $washTransaction->load(['customer.user', 'customerVehicle', 'primaryProduct', 'products', 'staff.user']),
                'Wash transaction completed successfully'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function cancel(WashTransaction $washTransaction)
    {
        try {
            if ($washTransaction->isCancelled()) {
                return $this->errorResponse('Wash transaction is already cancelled', 400);
            }

            if ($washTransaction->isCompleted()) {
                return $this->errorResponse('Cannot cancel a completed wash transaction', 400);
            }

            $washTransaction->update(['status' => WashTransaction::STATUS_CANCELLED]);
            return $this->successResponse(
                $washTransaction->load(['customer.user', 'customerVehicle', 'primaryProduct', 'products', 'staff.user']),
                'Wash transaction cancelled successfully'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getPreviousTransactionNumber()
    {
        try {
            $lastTransaction = WashTransaction::whereDate('created_at', now())
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastTransaction) {
                $today = now()->format('Ymd');
                $nextNumber = sprintf('TRX-%s-%03d', $today, 1);
                return $this->successResponse([
                    'transaction_number' => $nextNumber,
                    'date' => now()->format('Y-m-d'),
                    'sequence' => 1
                ], 'Next transaction number generated successfully');
            }

            // Extract the sequence number from the last transaction
            if (preg_match('/TRX-\d{8}-(\d{3})/', $lastTransaction->transaction_number, $matches)) {
                $sequence = (int)$matches[1];
                $today = now()->format('Ymd');
                $nextNumber = sprintf('TRX-%s-%03d', $today, $sequence);
                return $this->successResponse([
                    'transaction_number' => $nextNumber,
                    'date' => now()->format('Y-m-d'),
                    'sequence' => $sequence
                ], 'Previous transaction number retrieved successfully');
            }

            return $this->errorResponse('Failed to generate transaction number', 400);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getNextTransactionNumber()
    {
        try {
            $lastTransaction = WashTransaction::whereDate('created_at', now())
                ->orderBy('created_at', 'desc')
                ->first();

            $today = now()->format('Ymd');
            $sequence = 1;

            if ($lastTransaction) {
                // Extract the sequence number from the last transaction
                if (preg_match('/TRX-\d{8}-(\d{3})/', $lastTransaction->transaction_number, $matches)) {
                    $sequence = (int)$matches[1] + 1;
                }
            }

            $nextNumber = sprintf('TRX-%s-%03d', $today, $sequence);

            return $this->successResponse([
                'transaction_number' => $nextNumber,
                'date' => now()->format('Y-m-d'),
                'sequence' => $sequence
            ], 'Next transaction number generated successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Start service for wash transaction
     */
    public function startService(WashTransaction $washTransaction)
    {
        try {
            if ($washTransaction->isInService()) {
                return $this->errorResponse('Service is already in progress', 400);
            }

            if ($washTransaction->isServiceCompleted()) {
                return $this->errorResponse('Service is already completed', 400);
            }

            $washTransaction->startService();

            return $this->successResponse(
                $washTransaction->load([
                    'workOrder',
                    'customer.user',
                    'customerVehicle',
                    'products',
                    'user',
                    'shift',
                    'posTransaction'
                ]),
                'Service started successfully'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Complete service for wash transaction
     */
    public function completeService(WashTransaction $washTransaction)
    {
        try {
            if (!$washTransaction->isInService()) {
                return $this->errorResponse('Service must be in progress to complete', 400);
            }

            $washTransaction->completeService();

            return $this->successResponse(
                $washTransaction->load([
                    'workOrder',
                    'customer.user',
                    'customerVehicle',
                    'products',
                    'user',
                    'shift',
                    'posTransaction'
                ]),
                'Service completed successfully'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get queue information for wash transactions
     */
    public function getServiceQueue()
    {
        try {
            $today = now()->format('Y-m-d');

            $queue = WashTransaction::with([
                'workOrder',
                'customer.user',
                'customerVehicle.vehicle'
            ])
                ->whereDate('wash_date', $today)
                ->whereIn('service_status', [
                    WashTransaction::SERVICE_STATUS_WAITING,
                    WashTransaction::SERVICE_STATUS_IN_SERVICE
                ])
                ->orderBy('queue_number')
                ->get();

            return $this->successResponse([
                'queue' => $queue,
                'total_queue' => $queue->count(),
                'waiting' => $queue->where('service_status', WashTransaction::SERVICE_STATUS_WAITING)->count(),
                'in_service' => $queue->where('service_status', WashTransaction::SERVICE_STATUS_IN_SERVICE)->count(),
                'date' => $today
            ], 'Service queue information retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
