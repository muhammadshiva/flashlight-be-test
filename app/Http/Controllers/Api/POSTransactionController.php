<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\POSTransaction;
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

class POSTransactionController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of POS transactions
     */
    public function index(Request $request)
    {
        try {
            $query = POSTransaction::with([
                'washTransaction.workOrder',
                'customer.user',
                'customerVehicle.vehicle',
                'user',
                'shift',
                'products'
            ]);

            // Filter by shift_id if provided
            if ($request->has('shift_id')) {
                $query->where('shift_id', $request->shift_id);
            }

            // Filter by user_id (cashier) if provided
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range if provided
            if ($request->has('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            $transactions = $query->latest('transaction_date')->get();

            // Format the response
            $transactions->transform(function ($transaction) {
                $transaction->subtotal = (float) $transaction->subtotal;
                $transaction->tax_amount = (float) $transaction->tax_amount;
                $transaction->discount_amount = (float) $transaction->discount_amount;
                $transaction->total_amount = (float) $transaction->total_amount;
                $transaction->amount_paid = (float) $transaction->amount_paid;
                $transaction->change_amount = (float) $transaction->change_amount;
                return $transaction;
            });

            return $this->successResponse($transactions, 'POS transactions retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created POS transaction
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'work_order_id' => 'nullable|exists:work_orders,id',
                'customer_id' => 'required|exists:customers,id',
                'customer_vehicle_id' => 'required|exists:customer_vehicles,id',
                'user_id' => 'required|exists:users,id',
                'payment_method' => 'required|in:cash,qris,transfer,e_wallet',
                'amount_paid' => 'required|numeric|min:0',
                'transaction_date' => 'required|date',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'discount_amount' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                // Generate transaction number
                $transactionNumber = POSTransaction::generateTransactionNumber();

                // Calculate amounts
                $subtotal = 0;
                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    $subtotal += $product->price * $productData['quantity'];
                }

                $discountAmount = $request->discount_amount ?? 0;
                $taxAmount = 0; // Can be calculated if needed
                $totalAmount = $subtotal + $taxAmount - $discountAmount;

                // Calculate change
                $changeAmount = max(0, $request->amount_paid - $totalAmount);

                // Get active shift for the user
                $activeShift = Shift::getActiveShift($request->user_id);

                // Create POS transaction
                $transaction = POSTransaction::create([
                    'transaction_number' => $transactionNumber,
                    'work_order_id' => $request->work_order_id,
                    'customer_id' => $request->customer_id,
                    'customer_vehicle_id' => $request->customer_vehicle_id,
                    'user_id' => $request->user_id,
                    'shift_id' => $activeShift?->id,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'amount_paid' => $request->amount_paid,
                    'change_amount' => $changeAmount,
                    'transaction_date' => $request->transaction_date,
                    'status' => POSTransaction::STATUS_COMPLETED,
                    'notes' => $request->notes,
                    'completed_at' => now(),
                ]);

                // Attach products with their quantities and prices
                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    $subtotalProduct = $product->price * $productData['quantity'];

                    $transaction->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'price' => $product->price,
                        'subtotal' => $subtotalProduct,
                    ]);
                }

                // If this transaction is from a work order, update work order status
                if ($request->work_order_id) {
                    $workOrder = WorkOrder::findOrFail($request->work_order_id);
                    $workOrder->update(['status' => WorkOrder::STATUS_READY_FOR_PICKUP]);
                }

                // Update customer transaction counts
                $transaction->customer->updateTransactionCounts();

                DB::commit();

                $transaction->load([
                    'workOrder',
                    'customer.user',
                    'customerVehicle.vehicle',
                    'user',
                    'shift',
                    'products'
                ]);

                // Format the response
                $transaction->subtotal = (float) $transaction->subtotal;
                $transaction->tax_amount = (float) $transaction->tax_amount;
                $transaction->discount_amount = (float) $transaction->discount_amount;
                $transaction->total_amount = (float) $transaction->total_amount;
                $transaction->amount_paid = (float) $transaction->amount_paid;
                $transaction->change_amount = (float) $transaction->change_amount;

                return $this->successResponse([
                    'transaction' => $transaction,
                    'transaction_number' => $transaction->transaction_number,
                ], 'POS transaction created successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified POS transaction
     */
    public function show(POSTransaction $posTransaction)
    {
        try {
            $posTransaction->load([
                'workOrder',
                'customer.user',
                'customerVehicle.vehicle',
                'user',
                'shift',
                'products'
            ]);

            // Format amounts as float
            $posTransaction->subtotal = (float) $posTransaction->subtotal;
            $posTransaction->tax_amount = (float) $posTransaction->tax_amount;
            $posTransaction->discount_amount = (float) $posTransaction->discount_amount;
            $posTransaction->total_amount = (float) $posTransaction->total_amount;
            $posTransaction->amount_paid = (float) $posTransaction->amount_paid;
            $posTransaction->change_amount = (float) $posTransaction->change_amount;

            return $this->successResponse($posTransaction, 'POS transaction retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified POS transaction
     */
    public function update(Request $request, POSTransaction $posTransaction)
    {
        try {
            // Only allow updating certain fields for completed transactions
            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|required|in:pending,completed,cancelled,refunded',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $posTransaction->update($request->only(['status', 'notes']));

            return $this->successResponse(
                $posTransaction->load([
                    'workOrder',
                    'customer.user',
                    'customerVehicle.vehicle',
                    'user',
                    'shift',
                    'products'
                ]),
                'POS transaction updated successfully'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified POS transaction
     */
    public function destroy(POSTransaction $posTransaction)
    {
        try {
            // Only allow deletion of pending transactions
            if (!$posTransaction->isPending()) {
                return $this->errorResponse('Only pending transactions can be deleted', 400);
            }

            DB::beginTransaction();

            try {
                $customer = $posTransaction->customer;
                $posTransaction->delete();

                // Update customer transaction counts
                $customer->updateTransactionCounts();

                DB::commit();
                return $this->successResponse(null, 'POS transaction deleted successfully', 204);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Process payment for wash transaction
     */
    public function processWashTransactionPayment(Request $request, WashTransaction $washTransaction)
    {
        try {
            // Check if wash transaction already has payment
            if ($washTransaction->hasPayment()) {
                return $this->errorResponse('Wash transaction already has payment', 400);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'payment_method' => 'required|in:cash,qris,transfer,e_wallet',
                'amount_paid' => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'reference_number' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                // Get active shift for the user
                $activeShift = Shift::getActiveShift($request->user_id);

                $paymentData = [
                    'user_id' => $request->user_id,
                    'shift_id' => $activeShift?->id,
                    'payment_method' => $request->payment_method,
                    'amount_paid' => $request->amount_paid,
                    'discount_amount' => $request->discount_amount ?? 0,
                    'tax_amount' => $request->tax_amount ?? 0,
                    'notes' => $request->notes,
                    'reference_number' => $request->reference_number,
                ];

                // Create POS transaction from wash transaction
                $transaction = POSTransaction::createFromWashTransaction($washTransaction, $paymentData);

                DB::commit();

                $transaction->load([
                    'washTransaction.workOrder',
                    'customer.user',
                    'customerVehicle.vehicle',
                    'user',
                    'shift',
                    'products'
                ]);

                return $this->successResponse([
                    'transaction' => $transaction,
                    'wash_transaction' => $washTransaction->load(['workOrder', 'customer.user', 'customerVehicle.vehicle', 'products']),
                ], 'Wash transaction payment processed successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Process payment for work order (backward compatibility)
     */
    public function processWorkOrderPayment(Request $request, WorkOrder $workOrder)
    {
        try {
            // Check if work order already has payment
            if ($workOrder->hasPayment()) {
                return $this->errorResponse('Work order already has payment', 400);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'payment_method' => 'required|in:cash,qris,transfer,e_wallet',
                'amount_paid' => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            try {
                // Generate transaction number
                $transactionNumber = POSTransaction::generateTransactionNumber();

                // Calculate amounts from work order
                $subtotal = $workOrder->total_price;
                $discountAmount = $request->discount_amount ?? 0;
                $taxAmount = 0;
                $totalAmount = $subtotal + $taxAmount - $discountAmount;

                // Calculate change
                $changeAmount = max(0, $request->amount_paid - $totalAmount);

                // Get active shift for the user
                $activeShift = Shift::getActiveShift($request->user_id);

                // Create POS transaction
                $transaction = POSTransaction::create([
                    'transaction_number' => $transactionNumber,
                    'work_order_id' => $workOrder->id,
                    'customer_id' => $workOrder->customer_id,
                    'customer_vehicle_id' => $workOrder->customer_vehicle_id,
                    'user_id' => $request->user_id,
                    'shift_id' => $activeShift?->id,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'amount_paid' => $request->amount_paid,
                    'change_amount' => $changeAmount,
                    'transaction_date' => now(),
                    'status' => POSTransaction::STATUS_COMPLETED,
                    'notes' => $request->notes,
                    'completed_at' => now(),
                ]);

                // Copy products from work order to POS transaction
                foreach ($workOrder->products as $product) {
                    $transaction->products()->attach($product->id, [
                        'quantity' => $product->pivot->quantity,
                        'price' => $product->pivot->price,
                        'subtotal' => $product->pivot->subtotal,
                    ]);
                }

                // Update work order status
                $workOrder->update(['status' => WorkOrder::STATUS_READY_FOR_PICKUP]);

                DB::commit();

                $transaction->load([
                    'workOrder',
                    'customer.user',
                    'customerVehicle.vehicle',
                    'user',
                    'shift',
                    'products'
                ]);

                return $this->successResponse([
                    'transaction' => $transaction,
                    'work_order' => $workOrder->load(['customer.user', 'customerVehicle.vehicle', 'products']),
                ], 'Work order payment processed successfully', 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get transactions by customer
     */
    public function getByCustomerId($customerId)
    {
        try {
            $transactions = POSTransaction::with([
                'workOrder',
                'customer.user',
                'customerVehicle.vehicle',
                'user',
                'shift',
                'products'
            ])
                ->where('customer_id', $customerId)
                ->latest('transaction_date')
                ->get();

            // Format the response
            $transactions->transform(function ($transaction) {
                $transaction->subtotal = (float) $transaction->subtotal;
                $transaction->tax_amount = (float) $transaction->tax_amount;
                $transaction->discount_amount = (float) $transaction->discount_amount;
                $transaction->total_amount = (float) $transaction->total_amount;
                $transaction->amount_paid = (float) $transaction->amount_paid;
                $transaction->change_amount = (float) $transaction->change_amount;
                return $transaction;
            });

            return $this->successResponse($transactions, 'Customer POS transactions retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get daily sales report
     */
    public function getDailySalesReport(Request $request)
    {
        try {
            $date = $request->get('date', now()->format('Y-m-d'));

            $transactions = POSTransaction::with(['user', 'shift'])
                ->whereDate('transaction_date', $date)
                ->where('status', POSTransaction::STATUS_COMPLETED)
                ->get();

            $summary = [
                'date' => $date,
                'total_transactions' => $transactions->count(),
                'total_sales' => $transactions->sum('total_amount'),
                'total_discount' => $transactions->sum('discount_amount'),
                'total_tax' => $transactions->sum('tax_amount'),
                'payment_methods' => [
                    'cash' => $transactions->where('payment_method', 'cash')->sum('total_amount'),
                    'qris' => $transactions->where('payment_method', 'qris')->sum('total_amount'),
                    'transfer' => $transactions->where('payment_method', 'transfer')->sum('total_amount'),
                    'e_wallet' => $transactions->where('payment_method', 'e_wallet')->sum('total_amount'),
                ],
                'transactions' => $transactions
            ];

            return $this->successResponse($summary, 'Daily sales report retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
