<?php

namespace App\Services;

use App\Models\WashTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QRISService
{
    protected $merchantId;
    protected $merchantName;

    public function __construct()
    {
        $this->merchantId = config('services.qris.merchant_id', '1234567890');
        $this->merchantName = config('services.qris.merchant_name', 'FLASHLIGHT WASH');
    }

    /**
     * Generate QRIS code for payment
     */
    public function generateQRIS(WashTransaction $transaction): array
    {
        try {
            // In real implementation, integrate with QRIS provider (e.g., DANA, OVO, GoPay)
            $qrisData = [
                'qr_code' => $this->generateQRCode($transaction),
                'qr_string' => $this->generateQRString($transaction),
                'transaction_id' => 'QRIS-' . time() . '-' . Str::random(6),
                'amount' => $transaction->total_price,
                'expires_at' => now()->addMinutes(15),
                'status' => 'pending'
            ];

            Log::info('QRIS generated', [
                'transaction_id' => $transaction->id,
                'qris_data' => $qrisData
            ]);

            return $qrisData;
        } catch (\Exception $e) {
            Log::error('QRIS generation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check QRIS payment status
     */
    public function checkPaymentStatus(string $qrisTransactionId): array
    {
        try {
            // In real implementation, check with QRIS provider
            // For demo purposes, simulate random success/pending
            $statuses = ['pending', 'completed', 'failed'];
            $status = $statuses[array_rand($statuses)];

            Log::info('QRIS status check', [
                'qris_transaction_id' => $qrisTransactionId,
                'status' => $status
            ]);

            return [
                'status' => $status,
                'transaction_id' => $qrisTransactionId,
                'paid_at' => $status === 'completed' ? now() : null,
                'message' => $this->getStatusMessage($status)
            ];
        } catch (\Exception $e) {
            Log::error('QRIS status check failed', [
                'qris_transaction_id' => $qrisTransactionId,
                'error' => $e->getMessage()
            ]);
            return [
                'status' => 'failed',
                'message' => 'Failed to check payment status'
            ];
        }
    }

    /**
     * Generate QR code image data
     */
    protected function generateQRCode(WashTransaction $transaction): string
    {
        // In real implementation, use QR code library like SimpleSoftwareIO/simple-qrcode
        // For now, return placeholder
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }

    /**
     * Generate QRIS string
     */
    protected function generateQRString(WashTransaction $transaction): string
    {
        // Standard QRIS format (simplified)
        $qrisString = '00020101021143310016COM.NOBUBANK.WWW01189360050300000898240012' .
            $this->merchantId .
            '0303UME51440014ID.CO.QRIS.WWW0215ID20' .
            time() .
            '0303UME5204541253033605802ID5909' .
            strtoupper($this->merchantName) .
            '6007JAKARTA61051234562070703A016304';

        return $qrisString;
    }

    /**
     * Get status message
     */
    protected function getStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Waiting for customer payment',
            'completed' => 'Payment completed successfully',
            'failed' => 'Payment failed or expired',
            default => 'Unknown status'
        };
    }

    /**
     * Process QRIS payment completion
     */
    public function processPaymentCompletion(Payment $payment, string $qrisTransactionId): bool
    {
        try {
            $payment->update([
                'qris_transaction_id' => $qrisTransactionId,
                'status' => Payment::STATUS_COMPLETED,
                'paid_at' => now()
            ]);

            // Update wash transaction status to completed
            $payment->washTransaction->update([
                'status' => WashTransaction::STATUS_COMPLETED,
                'payment_method' => 'cashless'
            ]);

            Log::info('QRIS payment completed', [
                'payment_id' => $payment->id,
                'qris_transaction_id' => $qrisTransactionId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('QRIS payment completion failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
