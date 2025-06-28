<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\WashTransaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    use ApiResponse;

    /**
     * Start a new shift
     * POST /api/shifts/start
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'initial_cash' => 'required|numeric|min:0',
            'received_from' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        // Check if user has active shift
        if (Shift::hasActiveShift($user->id)) {
            return $this->errorResponse('You already have an active shift. Please close the current shift first.', 400);
        }

        try {
            DB::beginTransaction();

            $shift = Shift::create([
                'user_id' => $user->id,
                'start_time' => now(),
                'initial_cash' => $request->initial_cash,
                'received_from' => $request->received_from,
                'status' => Shift::STATUS_ACTIVE,
            ]);

            DB::commit();

            return $this->successResponse([
                'shift' => $shift->load('user:id,name,email')
            ], 'Shift started successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to start shift: ' . $e->getMessage(), 500);
        }
    }

    /**
     * End current shift
     * POST /api/shifts/end
     */
    public function end(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'physical_cash' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        // Get active shift
        $shift = Shift::getActiveShift($user->id);

        if (!$shift) {
            return $this->errorResponse('No active shift found', 404);
        }

        try {
            DB::beginTransaction();

            // Close the shift with calculations
            $shift->close($request->physical_cash);

            // Calculate difference
            $difference = $shift->calculateCashDifference();

            DB::commit();

            return $this->successResponse([
                'shift' => $shift->load('user:id,name,email'),
                'summary' => [
                    'initial_cash' => $shift->initial_cash,
                    'total_sales' => $shift->total_sales,
                    'expected_cash' => $shift->initial_cash + $shift->total_sales,
                    'final_cash' => $shift->final_cash,
                    'difference' => $difference,
                    'total_transactions' => $shift->washTransactions()->count(),
                    'shift_duration' => $shift->start_time->diffForHumans($shift->end_time),
                ]
            ], 'Shift ended successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to end shift: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get current active shift
     * GET /api/shifts/current
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $shift = Shift::getActiveShift($user->id);

        if (!$shift) {
            return $this->errorResponse('No active shift found', 404);
        }

        $currentSales = $shift->calculateTotalSales();

        return $this->successResponse([
            'shift' => $shift->load('user:id,name,email'),
            'current_stats' => [
                'initial_cash' => $shift->initial_cash,
                'current_sales' => $currentSales,
                'total_transactions' => $shift->washTransactions()->count(),
                'start_time' => $shift->start_time,
                'duration' => $shift->start_time->diffForHumans(),
            ]
        ], 'Active shift retrieved successfully');
    }

    /**
     * Get shift history
     * GET /api/shifts/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $shifts = Shift::where('user_id', $user->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->successResponse($shifts, 'Shift history retrieved successfully');
    }

    /**
     * Get shift details
     * GET /api/shifts/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $shift = Shift::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['user:id,name,email', 'washTransactions'])
            ->first();

        if (!$shift) {
            return $this->errorResponse('Shift not found', 404);
        }

        $summary = [
            'initial_cash' => $shift->initial_cash,
            'total_sales' => $shift->total_sales,
            'final_cash' => $shift->final_cash,
            'difference' => $shift->calculateCashDifference(),
            'total_transactions' => $shift->washTransactions()->count(),
            'completed_transactions' => $shift->washTransactions()->where('status', 'completed')->count(),
        ];

        if ($shift->end_time) {
            $summary['shift_duration'] = $shift->start_time->diffForHumans($shift->end_time);
        }

        return $this->successResponse([
            'shift' => $shift,
            'summary' => $summary
        ], 'Shift details retrieved successfully');
    }

    /**
     * Check if user has active shift
     * GET /api/shifts/status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $hasActiveShift = Shift::hasActiveShift($user->id);

        return $this->successResponse([
            'has_active_shift' => $hasActiveShift,
            'status' => $hasActiveShift ? 'active' : 'inactive'
        ], 'Shift status retrieved successfully');
    }

    /**
     * Get transactions for a specific shift
     * GET /api/shifts/{id}/transactions
     */
    public function transactions(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // Validate shift exists and belongs to user
        $shift = Shift::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$shift) {
            return $this->errorResponse('Shift not found', 404);
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        // Get transactions for this shift with relationships
        $transactionsQuery = WashTransaction::where('shift_id', $id)
            ->with([
                'customer.user',
                'products',
                'payments'
            ])
            ->orderBy('created_at', 'desc');

        $transactions = $transactionsQuery->paginate($perPage, ['*'], 'page', $page);

        // Format transactions according to specification
        $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
            // Get customer name
            $customerName = $transaction->customer && $transaction->customer->user
                ? $transaction->customer->user->name
                : 'Unknown Customer';

            // Format items
            $items = $transaction->products->map(function ($product) {
                return [
                    'name' => $product->name,
                    'qty' => $product->pivot->quantity,
                    'price' => (float) $product->pivot->price,
                    'subtotal' => (float) $product->pivot->subtotal,
                ];
            })->toArray();

            // Calculate payment totals
            $cashTotal = 0;
            $debitTotal = 0;

            foreach ($transaction->payments as $payment) {
                if ($payment->method === 'cash') {
                    $cashTotal += (float) $payment->amount_paid;
                } else {
                    // Group qris, transfer, e_wallet as "debit"
                    $debitTotal += (float) $payment->amount_paid;
                }
            }

            return [
                'invoice_number' => $transaction->transaction_number,
                'time' => $transaction->created_at->format('H:i'),
                'customer_name' => $customerName,
                'items' => $items,
                'payment' => [
                    'cash' => $cashTotal,
                    'debit' => $debitTotal,
                ],
                'total' => (float) $transaction->total_price,
            ];
        });

        // Calculate overall totals for all transactions in this shift
        $allTransactions = WashTransaction::where('shift_id', $id)
            ->with('payments')
            ->get();

        $totalCash = 0;
        $totalDebit = 0;

        foreach ($allTransactions as $transaction) {
            foreach ($transaction->payments as $payment) {
                if ($payment->method === 'cash') {
                    $totalCash += (float) $payment->amount_paid;
                } else {
                    $totalDebit += (float) $payment->amount_paid;
                }
            }
        }

        return $this->successResponse([
            'current_page' => $transactions->currentPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
            'transactions' => $formattedTransactions,
            'totals' => [
                'cash' => $totalCash,
                'debit' => $totalDebit,
            ],
        ], 'Shift transactions retrieved successfully');
    }
}
