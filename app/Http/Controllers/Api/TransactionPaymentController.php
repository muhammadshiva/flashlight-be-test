<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WashTransaction;
use App\Models\User;
use App\Services\FCMService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            ]);

            DB::beginTransaction();

            // Get the wash transaction
            $washTransaction = WashTransaction::with(['customer.user', 'user', 'customerVehicle', 'products'])->findOrFail($request->wash_transaction_id);

            // Verify transaction is valid for payment processing
            if ($washTransaction->isCompleted()) {
                return $this->errorResponse('Transaction has already been completed.', 400);
            }

            if ($washTransaction->isCancelled()) {
                return $this->errorResponse('Cannot process payment for cancelled transaction.', 400);
            }

            // Update wash transaction but don't complete yet - let cashier process payment
            $washTransaction->update([
                'status' => WashTransaction::STATUS_IN_PROGRESS,
                'total_price' => $request->total_amount,
                'payment_method' => $request->payment_method,
            ]);

            DB::commit();

            // Prepare response data
            $responseData = [
                'wash_transaction' => $washTransaction->load(['customer', 'customerVehicle', 'products']),
                'message' => 'Transaction updated and ready for payment processing by cashier.',
            ];

            return $this->successResponse($responseData, 'Transaction data received successfully and ready for cashier processing');
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
            $user = auth()->user();

            // Find the most recent completed transaction for this customer
            $query = WashTransaction::with(['payments', 'customer', 'customerVehicle.vehicle', 'products'])
                ->where('status', WashTransaction::STATUS_COMPLETED)
                ->orderBy('updated_at', 'desc');

            // If user is a customer, filter by customer_id
            if ($user->isCustomer() && $user->customer) {
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
