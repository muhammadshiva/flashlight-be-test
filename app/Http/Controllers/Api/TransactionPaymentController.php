<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Voucher;
use App\Models\PremiumCounter;
use App\Models\WashTransaction;
use App\Models\User;
use App\Services\FCMService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TransactionPaymentController extends Controller
{
    use ApiResponse;

    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Receive transaction data from client after payment completion by cashier
     */
    public function sendTransactionData(Request $request)
    {
        try {
            $request->validate([
                'total_amount' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', 'string', 'in:cash,qris,transfer,e_wallet'],
                'wash_transaction_id' => ['required', 'integer', 'exists:wash_transactions,id'],
                'amount_paid' => ['nullable', 'numeric', 'min:0'],
                'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
                'notes' => ['nullable', 'string', 'max:500'],
                'voucher_code' => ['nullable', 'string', 'max:100'],
                'benefit_fd_amount' => ['nullable', 'numeric', 'min:0'],
            ]);

            DB::beginTransaction();

            // Get the wash transaction
            $washTransaction = WashTransaction::with(['customer.user', 'user', 'customerVehicle', 'products', 'workOrder'])->findOrFail($request->wash_transaction_id);

            // Verify transaction is valid for payment processing
            if ($washTransaction->isCompleted()) {
                return $this->errorResponse('Transaction has already been completed.', 400);
            }

            if ($washTransaction->isCancelled()) {
                return $this->errorResponse('Cannot process payment for cancelled transaction.', 400);
            }

            // Update wash transaction
            $washTransaction->update([
                'status' => WashTransaction::STATUS_IN_PROGRESS,
                'total_price' => $request->total_amount,
                'payment_method' => $request->payment_method,
            ]);

            // Create payment
            $amountPaid = $request->amount_paid ?? $request->total_amount;
            $change = max(0, $amountPaid - $request->total_amount);
            $payment = Payment::create([
                'wash_transaction_id' => $washTransaction->id,
                'user_id' => Auth::id(),
                'work_order_id' => $washTransaction->work_order_id,
                'method' => $this->mapPaymentMethod($request->payment_method),
                'amount_paid' => $amountPaid,
                'change_amount' => $change,
                'status' => Payment::STATUS_COMPLETED,
                'paid_at' => now(),
            ]);

            // Add line items from services
            $grossTotal = 0.0;
            foreach ($washTransaction->services as $service) {
                $lineAmount = (float) ($service->pivot->subtotal ?? 0);
                $grossTotal += $lineAmount;
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'line_type' => 'service',
                    'reference_id' => $service->id,
                    'description' => $service->name,
                    'quantity' => (int) ($service->pivot->quantity ?? 1),
                    'unit_amount' => (float) ($service->pivot->price ?? 0),
                    'line_total' => $lineAmount,
                ]);
            }

            // Add line items from F&D
            foreach ($washTransaction->fds as $fd) {
                $lineAmount = (float) ($fd->pivot->subtotal ?? 0);
                $grossTotal += $lineAmount;
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'line_type' => 'fd',
                    'reference_id' => $fd->id,
                    'description' => $fd->name,
                    'quantity' => (int) ($fd->pivot->quantity ?? 1),
                    'unit_amount' => (float) ($fd->pivot->price ?? 0),
                    'line_total' => $lineAmount,
                ]);
            }

            // Apply voucher
            if ($request->filled('voucher_code')) {
                $voucher = Voucher::where('code', $request->voucher_code)->where('status', 'active')->first();
                if ($voucher) {
                    $discount = 0.0;
                    if ($voucher->type === 'free_wash') {
                        $primary = $washTransaction->services()->where('service_item_id', $washTransaction->main_service_item_id)->first();
                        $discount = (float) ($primary?->pivot?->subtotal ?? 0);
                    } elseif ($voucher->type === 'discount_amount') {
                        $discount = (float) $voucher->amount;
                    } elseif ($voucher->type === 'discount_percent') {
                        $discount = round($grossTotal * ((int) $voucher->percent) / 100, 2);
                    }

                    if ($discount > 0) {
                        PaymentItem::create([
                            'payment_id' => $payment->id,
                            'line_type' => 'voucher',
                            'reference_id' => $voucher->id,
                            'description' => 'Voucher ' . $voucher->code,
                            'quantity' => 1,
                            'unit_amount' => -abs($discount),
                            'line_total' => -abs($discount),
                        ]);
                        $grossTotal -= abs($discount);
                        $voucher->update(['status' => 'used', 'used_at' => now()]);
                    }
                }
            }

            // Apply Friend FD benefit
            if ($request->filled('benefit_fd_amount') && (float) $request->benefit_fd_amount > 0) {
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'line_type' => 'benefit_fd',
                    'reference_id' => null,
                    'description' => 'Friend F&D Benefit',
                    'quantity' => 1,
                    'unit_amount' => -abs((float) $request->benefit_fd_amount),
                    'line_total' => -abs((float) $request->benefit_fd_amount),
                ]);
                $grossTotal -= abs((float) $request->benefit_fd_amount);
            }

            // Update transaction status to completed
            $washTransaction->update(['status' => WashTransaction::STATUS_COMPLETED]);

            // Premium counter handling for premium transactions
            $isPremium = (bool) $washTransaction->primaryProduct?->is_premium;
            if ($isPremium && $washTransaction->customer) {
                $month = (int) date('n');
                $year = (int) date('Y');
                $counter = PremiumCounter::firstOrCreate([
                    'customer_id' => $washTransaction->customer->id,
                    'month' => $month,
                    'year' => $year,
                ]);
                $counter->increment('premium_count');
                $counter->refresh();
                if ($counter->premium_count > 0 && $counter->premium_count % 5 === 0) {
                    Voucher::create([
                        'code' => 'FW-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                        'customer_id' => $washTransaction->customer->id,
                        'type' => 'free_wash',
                        'status' => 'active',
                        'issued_at' => now(),
                        'activated_at' => now(),
                        'expires_at' => now()->addMonth(),
                    ]);
                }
            }

            DB::commit();

            $this->sendPaymentCompletionNotification($washTransaction->fresh(), $payment->fresh());

            return $this->successResponse([
                'wash_transaction' => $washTransaction->fresh()->load(['customer', 'customerVehicle', 'products']),
                'payment' => $payment->load('items'),
            ], 'Payment completed');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Transaction payment processing failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return $this->handleException($e);
        }
    }

    /**
     * Get ongoing transaction data for customer
     */
    public function getOngoingTransactionData(Request $request)
    {
        try {
            $user = Auth::user();

            // Find the most recent completed transaction for this customer
            $query = WashTransaction::with(['payments', 'customer', 'customerVehicle.vehicle', 'products'])
                ->where('status', WashTransaction::STATUS_COMPLETED)
                ->orderBy('updated_at', 'desc');

            // If user is a customer, filter by customer_id
            if ($user && (($user->type ?? null) === \App\Models\User::TYPE_CUSTOMER) && $user->customer) {
                $query->where('customer_id', $user->customer->id);
            }

            // Get specific transaction if ID provided
            if ($request->filled('wash_transaction_id')) {
                $query->where('id', $request->wash_transaction_id);
            }

            $transaction = $query->first();

            if (!$transaction) {
                return $this->errorResponse('No completed transaction found.', 404);
            }

            // Get the latest payment for this transaction
            $latestPayment = $transaction->payments()->latest()->first();

            if (!$latestPayment) {
                return $this->errorResponse('No payment record found for this transaction.', 404);
            }

            // Prepare response data
            $responseData = [
                'wash_transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'total_amount' => $transaction->total_price,
                'amount_paid' => $latestPayment->amount_paid,
                'total_change' => $latestPayment->change_amount,
                'payment_method' => $latestPayment->method,
                'is_print_receipt' => $this->shouldPrintReceipt($latestPayment),
                'customer_name' => $transaction->customer->user->name ?? 'Guest',
                'vehicle_info' => $transaction->customerVehicle ? [
                    'license_plate' => $transaction->customerVehicle->license_plate,
                    'vehicle_name' => $transaction->customerVehicle->vehicle ?
                        $transaction->customerVehicle->vehicle->brand . ' ' . $transaction->customerVehicle->vehicle->model :
                        'Unknown Vehicle',
                ] : null,
                'services' => $transaction->products->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'price' => $product->pivot->price ?? $product->price,
                        'quantity' => $product->pivot->quantity ?? 1,
                    ];
                }),
                'completed_at' => $transaction->updated_at,
                'payment_completed_at' => $latestPayment->paid_at,
            ];

            return $this->successResponse($responseData, 'Transaction data retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to get ongoing transaction data: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    /**
     * Send FCM notification for payment completion
     */
    private function sendPaymentCompletionNotification(WashTransaction $transaction, Payment $payment)
    {
        try {
            $customer = $transaction->customer;
            $user = $customer->user;

            if (!$user || !$user->fcm_token) {
                Log::info('No FCM token available for customer: ' . $customer->id);
                return;
            }

            $title = 'Pembayaran Selesai!';
            $body = "Pembayaran untuk transaksi #{$transaction->transaction_number} sebesar Rp " . number_format($payment->amount_paid, 0, ',', '.') . " telah berhasil diverifikasi.";

            $notificationData = [
                'is_print_receipt' => $this->shouldPrintReceipt($payment),
                'wash_transaction_id' => (string) $transaction->id,
            ];

            $result = $this->fcmService->sendNotification(
                $user->fcm_token,
                $title,
                $body,
                $notificationData
            );

            Log::info('Payment completion notification sent', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'notification_result' => $result,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send payment completion notification: ' . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map payment method from request to system format
     */
    private function mapPaymentMethod(string $method): string
    {
        $mapping = [
            'cash' => Payment::METHOD_CASH,
            'qris' => Payment::METHOD_QRIS,
            'transfer' => 'transfer',
            'e_wallet' => 'e_wallet',
        ];

        return $mapping[$method] ?? $method;
    }

    /**
     * Determine if receipt should be printed
     */
    private function shouldPrintReceipt(Payment $payment): bool
    {
        // Print receipt for cash payments and when change is given
        return $payment->isCash() || $payment->change_amount > 0;
    }
}
