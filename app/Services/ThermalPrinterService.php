<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\WashTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ThermalPrinterService
{
    protected $printerWidth;
    protected $printerDevice;
    protected $isConnected = false;
    protected $connectionAttempts = 0;
    protected $maxConnectionAttempts;

    public function __construct()
    {
        $settings = session('printer_settings', []);
        $this->printerWidth = $settings['printer_width'] ?? config('printer.paper.default_width', 58);
        $this->printerDevice = $settings['printer_device'] ?? null;
        $this->maxConnectionAttempts = config('printer.bluetooth.max_connection_attempts', 3);
    }

    /**
     * Scan for available Bluetooth devices
     */
    public function scanBluetoothDevices(): array
    {
        try {
            if (config('printer.diagnostics.log_scans', true)) {
                Log::info('Starting Bluetooth device scan');
            }

            $devices = [];

            // Check if mock devices are enabled
            if (config('printer.mock.enabled', false)) {
                return config('printer.mock.devices', []);
            }

            // Check if running on macOS
            if (PHP_OS === 'Darwin') {
                $devices = $this->scanMacOSBluetoothDevices();
            } elseif (PHP_OS_FAMILY === 'Linux') {
                $devices = $this->scanLinuxBluetoothDevices();
            } else {
                // For Windows or other OS, return mock data with connection status
                $devices = $this->getMockDevicesWithStatus();
            }

            if (config('printer.diagnostics.log_scans', true)) {
                Log::info('Bluetooth scan completed', ['devices_found' => count($devices)]);
            }
            return $devices;
        } catch (\Exception $e) {
            Log::error('Bluetooth scan failed', ['error' => $e->getMessage()]);
            // Return mock devices as fallback
            return $this->getMockDevicesWithStatus();
        }
    }

    /**
     * Scan Bluetooth devices on macOS
     */
    protected function scanMacOSBluetoothDevices(): array
    {
        try {
            // Use system_profiler to get Bluetooth devices on macOS
            $result = Process::run('system_profiler SPBluetoothDataType -json');

            if ($result->successful()) {
                $data = json_decode($result->output(), true);
                return $this->parseMacOSBluetoothData($data);
            }
        } catch (\Exception $e) {
            Log::warning('macOS Bluetooth scan failed', ['error' => $e->getMessage()]);
        }

        return $this->getMockDevicesWithStatus();
    }

    /**
     * Scan Bluetooth devices on Linux
     */
    protected function scanLinuxBluetoothDevices(): array
    {
        try {
            // Use bluetoothctl to scan for devices on Linux
            $result = Process::run('bluetoothctl devices');

            if ($result->successful()) {
                return $this->parseLinuxBluetoothData($result->output());
            }
        } catch (\Exception $e) {
            Log::warning('Linux Bluetooth scan failed', ['error' => $e->getMessage()]);
        }

        return $this->getMockDevicesWithStatus();
    }

    /**
     * Parse macOS Bluetooth data
     */
    protected function parseMacOSBluetoothData(array $data): array
    {
        $devices = [];

        if (isset($data['SPBluetoothDataType'])) {
            foreach ($data['SPBluetoothDataType'] as $bluetoothData) {
                if (isset($bluetoothData['device_title'])) {
                    foreach ($bluetoothData as $key => $device) {
                        if (is_array($device) && isset($device['device_name'])) {
                            $deviceName = $device['device_name'];
                            $isConnected = isset($device['device_isconnected']) && $device['device_isconnected'] === 'attrib_Yes';

                            // Check if it's likely a thermal printer
                            if ($this->isLikelyThermalPrinter($deviceName)) {
                                $devices[$key] = $deviceName . ($isConnected ? ' (Connected)' : ' (Disconnected)');
                            }
                        }
                    }
                }
            }
        }

        return empty($devices) ? $this->getMockDevicesWithStatus() : $devices;
    }

    /**
     * Parse Linux Bluetooth data
     */
    protected function parseLinuxBluetoothData(string $output): array
    {
        $devices = [];
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (preg_match('/Device\s+([A-Fa-f0-9:]+)\s+(.+)/', $line, $matches)) {
                $address = $matches[1];
                $name = trim($matches[2]);

                if ($this->isLikelyThermalPrinter($name)) {
                    // Check connection status
                    $connectionStatus = $this->checkLinuxDeviceConnection($address);
                    $devices[$address] = $name . ($connectionStatus ? ' (Connected)' : ' (Disconnected)');
                }
            }
        }

        return empty($devices) ? $this->getMockDevicesWithStatus() : $devices;
    }

    /**
     * Check if device name suggests it's a thermal printer
     */
    protected function isLikelyThermalPrinter(string $deviceName): bool
    {
        $thermalPrinterKeywords = config('printer.devices.thermal_printer_keywords', [
            'thermal',
            'printer',
            'xprinter',
            'epson',
            'receipt',
            'pos',
            'tm-',
            'rp-',
            'bluetooth printer',
            'mini printer'
        ]);

        $deviceNameLower = strtolower($deviceName);

        foreach ($thermalPrinterKeywords as $keyword) {
            if (strpos($deviceNameLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check Linux device connection status
     */
    protected function checkLinuxDeviceConnection(string $address): bool
    {
        try {
            $result = Process::run("bluetoothctl info $address");
            return $result->successful() && strpos($result->output(), 'Connected: yes') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get mock devices with connection status for fallback
     */
    protected function getMockDevicesWithStatus(): array
    {
        return config('printer.mock.devices', [
            'mock_printer_58' => 'Thermal Printer 58mm (Disconnected)',
            'mock_printer_80' => 'Thermal Printer 80mm (Disconnected)',
            'mock_xprinter' => 'Xprinter XP-58IIH (Disconnected)',
            'mock_epson' => 'Epson TM-T20III (Disconnected)',
        ]);
    }

    /**
     * Test printer connection with retry mechanism
     */
    public function testConnection(): bool
    {
        try {
            if (empty($this->printerDevice)) {
                Log::warning('No printer device selected');
                return false;
            }

            // Reset connection attempts
            $this->connectionAttempts = 0;

            // Try to establish connection with retries
            while ($this->connectionAttempts < $this->maxConnectionAttempts) {
                $this->connectionAttempts++;

                if (config('printer.diagnostics.log_connections', true)) {
                    Log::info('Attempting printer connection', [
                        'device' => $this->printerDevice,
                        'attempt' => $this->connectionAttempts
                    ]);
                }

                if ($this->attemptConnection()) {
                    $this->isConnected = true;
                    if (config('printer.diagnostics.log_connections', true)) {
                        Log::info('Printer connection successful', [
                            'device' => $this->printerDevice,
                            'attempts' => $this->connectionAttempts
                        ]);
                    }
                    return true;
                }

                // Wait before retry (except on last attempt)
                if ($this->connectionAttempts < $this->maxConnectionAttempts) {
                    $retryDelay = config('printer.bluetooth.connection_retry_delay', 2);
                    sleep($retryDelay);
                }
            }

            $this->isConnected = false;
            Log::error('Printer connection failed after all attempts', [
                'device' => $this->printerDevice,
                'attempts' => $this->connectionAttempts
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Printer connection test failed', ['error' => $e->getMessage()]);
            $this->isConnected = false;
            return false;
        }
    }

    /**
     * Attempt single connection to printer
     */
    protected function attemptConnection(): bool
    {
        try {
            // Check if device is mock device
            if (strpos($this->printerDevice, 'mock_') === 0) {
                // For mock devices, simulate connection based on device name
                return strpos($this->printerDevice, 'xprinter') !== false;
            }

            // For real devices, try to establish connection
            if (PHP_OS === 'Darwin') {
                return $this->connectMacOSDevice();
            } elseif (PHP_OS_FAMILY === 'Linux') {
                return $this->connectLinuxDevice();
            }

            // For other OS, just check if device is set
            return !empty($this->printerDevice);
        } catch (\Exception $e) {
            Log::warning('Connection attempt failed', [
                'device' => $this->printerDevice,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Connect to device on macOS
     */
    protected function connectMacOSDevice(): bool
    {
        try {
            // Use blueutil or bluetoothconnector if available
            $result = Process::run("blueutil --connect {$this->printerDevice}");
            return $result->successful();
        } catch (\Exception $e) {
            Log::warning('macOS device connection failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Connect to device on Linux
     */
    protected function connectLinuxDevice(): bool
    {
        try {
            // Use bluetoothctl to connect
            $result = Process::run("bluetoothctl connect {$this->printerDevice}");
            return $result->successful() && strpos($result->output(), 'Connection successful') !== false;
        } catch (\Exception $e) {
            Log::warning('Linux device connection failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Print receipt for payment
     */
    public function printReceipt(Payment $payment): bool
    {
        try {
            if (!$this->testConnection()) {
                throw new \Exception('Printer not connected');
            }

            $receiptData = $this->generateReceiptData($payment);

            // In a real implementation, you would send this data to the thermal printer
            // For now, we'll just log the receipt data
            Log::info('Printing receipt', [
                'payment_id' => $payment->id,
                'receipt_data' => $receiptData
            ]);

            // Update payment record
            $payment->update(['receipt_printed' => true]);

            return true;
        } catch (\Exception $e) {
            Log::error('Receipt printing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate receipt data for thermal printer
     */
    protected function generateReceiptData(Payment $payment): array
    {
        $transaction = $payment->washTransaction;
        $customer = $transaction->customer;
        $vehicle = $transaction->customerVehicle;

        $receiptLines = [];

        // Header
        $receiptLines[] = $this->centerText('FLASHLIGHT WASH');
        $receiptLines[] = $this->centerText('Car & Motorcycle Wash');
        $receiptLines[] = $this->repeatChar('=');
        $receiptLines[] = '';

        // Transaction Info
        $receiptLines[] = 'Transaction No: ' . $transaction->transaction_number;
        $receiptLines[] = 'Payment No: ' . $payment->payment_number;
        $receiptLines[] = 'Date: ' . $payment->paid_at->format('d/m/Y H:i:s');
        $receiptLines[] = 'Cashier: ' . $payment->staff->user->name;
        $receiptLines[] = '';

        // Customer Info
        $receiptLines[] = 'Customer: ' . $customer->user->name;
        if ($vehicle && $vehicle->vehicle) {
            $receiptLines[] = 'Vehicle: ' . $vehicle->vehicle->name;
            $receiptLines[] = 'License: ' . $vehicle->license_plate;
        }
        $receiptLines[] = '';

        // Services
        $receiptLines[] = $this->repeatChar('-');
        $receiptLines[] = 'SERVICES';
        $receiptLines[] = $this->repeatChar('-');

        // Primary service
        if ($transaction->primaryProduct) {
            $receiptLines[] = $this->formatServiceLine(
                $transaction->primaryProduct->name,
                1,
                $transaction->primaryProduct->price
            );
        }

        // Additional services
        foreach ($transaction->products as $product) {
            if ($product->id !== $transaction->product_id) {
                $receiptLines[] = $this->formatServiceLine(
                    $product->name,
                    $product->pivot->quantity,
                    $product->pivot->subtotal
                );
            }
        }

        $receiptLines[] = $this->repeatChar('-');

        // Payment Details
        $receiptLines[] = $this->formatPriceLine('Subtotal:', $transaction->total_price);
        $receiptLines[] = $this->formatPriceLine('Total:', $transaction->total_price);
        $receiptLines[] = '';
        $receiptLines[] = 'Payment Method: ' . strtoupper($payment->method);

        if ($payment->isCash()) {
            $receiptLines[] = $this->formatPriceLine('Cash:', $payment->amount_paid);
            $receiptLines[] = $this->formatPriceLine('Change:', $payment->change_amount);
        }

        $receiptLines[] = '';
        $receiptLines[] = $this->repeatChar('=');
        $receiptLines[] = $this->centerText('Thank You!');
        $receiptLines[] = $this->centerText('Visit Again Soon');
        $receiptLines[] = '';

        return $receiptLines;
    }

    /**
     * Center text based on printer width
     */
    protected function centerText(string $text): string
    {
        $maxChars = $this->getMaxCharsPerLine();
        $padding = max(0, ($maxChars - strlen($text)) / 2);
        return str_repeat(' ', (int)$padding) . $text;
    }

    /**
     * Repeat character across full width
     */
    protected function repeatChar(string $char = '-'): string
    {
        return str_repeat($char, $this->getMaxCharsPerLine());
    }

    /**
     * Format service line with quantity and price
     */
    protected function formatServiceLine(string $name, int $qty, float $price): string
    {
        $maxChars = $this->getMaxCharsPerLine();
        $qtyPrice = $qty . 'x ' . number_format($price, 0);
        $nameLength = $maxChars - strlen($qtyPrice) - 1;

        if (strlen($name) > $nameLength) {
            $name = substr($name, 0, $nameLength - 3) . '...';
        }

        return $name . str_repeat(' ', $nameLength - strlen($name)) . ' ' . $qtyPrice;
    }

    /**
     * Format price line
     */
    protected function formatPriceLine(string $label, float $amount): string
    {
        $maxChars = $this->getMaxCharsPerLine();
        $priceText = 'IDR ' . number_format($amount, 0);
        $labelLength = $maxChars - strlen($priceText);

        return $label . str_repeat(' ', $labelLength - strlen($label)) . $priceText;
    }

    /**
     * Get maximum characters per line based on printer width
     */
    protected function getMaxCharsPerLine(): int
    {
        $widthConfig = config('printer.paper.widths', []);

        if (isset($widthConfig[$this->printerWidth])) {
            return $widthConfig[$this->printerWidth]['max_chars'];
        }

        return match ($this->printerWidth) {
            58 => 32,
            80 => 48,
            default => 32
        };
    }

    /**
     * Get available Bluetooth printers
     */
    public function getAvailablePrinters(): array
    {
        return $this->scanBluetoothDevices();
    }

    /**
     * Connect to specific printer with improved error handling
     */
    public function connectToPrinter(string $deviceId): bool
    {
        try {
            Log::info('Attempting to connect to printer', ['device_id' => $deviceId]);

            $this->printerDevice = $deviceId;
            $this->isConnected = false;

            // Test the connection
            if ($this->testConnection()) {
                Log::info('Successfully connected to printer', ['device_id' => $deviceId]);
                return true;
            } else {
                Log::error('Failed to connect to printer', ['device_id' => $deviceId]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Printer connection error', [
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);
            $this->isConnected = false;
            return false;
        }
    }

    /**
     * Disconnect from printer
     */
    public function disconnect(): bool
    {
        try {
            Log::info('Disconnecting from printer', ['device' => $this->printerDevice]);

            if (!empty($this->printerDevice) && !strpos($this->printerDevice, 'mock_')) {
                // Attempt to disconnect real device
                if (PHP_OS === 'Darwin') {
                    Process::run("blueutil --disconnect {$this->printerDevice}");
                } elseif (PHP_OS_FAMILY === 'Linux') {
                    Process::run("bluetoothctl disconnect {$this->printerDevice}");
                }
            }

            $this->printerDevice = null;
            $this->isConnected = false;
            $this->connectionAttempts = 0;

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disconnect from printer', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if printer is connected
     */
    public function isConnected(): bool
    {
        return $this->isConnected && !empty($this->printerDevice);
    }

    /**
     * Get connection diagnostics
     */
    public function getConnectionDiagnostics(): array
    {
        return [
            'device' => $this->printerDevice,
            'connected' => $this->isConnected,
            'attempts' => $this->connectionAttempts,
            'max_attempts' => $this->maxConnectionAttempts,
            'os' => PHP_OS,
            'os_family' => PHP_OS_FAMILY,
        ];
    }
}
