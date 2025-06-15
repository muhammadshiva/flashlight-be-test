<div class="space-y-4">
    @if(isset($qr_code))
        <div class="flex flex-col items-center p-4 border rounded-lg bg-white">
            <div class="mb-4">
                <img src="{{ $qr_code }}" alt="QRIS Code" class="w-48 h-48 border" />
            </div>

            <div class="text-center space-y-2">
                <p class="text-sm font-medium text-gray-700">
                    Amount: IDR {{ number_format($amount ?? 0) }}
                </p>
                <p class="text-xs text-gray-500">
                    Transaction ID: {{ $transaction_id ?? 'N/A' }}
                </p>
                <p class="text-xs text-gray-500">
                    Expires: {{ isset($expires_at) ? $expires_at->format('H:i:s') : 'N/A' }}
                </p>
            </div>

            <div class="mt-4 p-3 bg-blue-50 rounded-lg w-full">
                <p class="text-sm text-blue-800 text-center">
                    Ask customer to scan this QR code with their mobile banking app
                </p>
            </div>
        </div>
    @else
        <div class="p-4 border rounded-lg bg-gray-50">
            <p class="text-center text-gray-500">
                QRIS code will be generated here
            </p>
        </div>
    @endif
</div>
