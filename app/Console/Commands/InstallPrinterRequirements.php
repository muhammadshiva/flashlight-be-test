<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class InstallPrinterRequirements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printer:check-requirements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and install system requirements for Bluetooth thermal printer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🖨️  Checking Bluetooth Thermal Printer Requirements...');
        $this->newLine();

        // Check OS
        $this->checkOperatingSystem();

        // Check Bluetooth availability
        $this->checkBluetoothAvailability();

        // Check printer config
        $this->checkConfiguration();

        // Provide instructions
        $this->provideInstructions();

        $this->newLine();
        $this->info('✅ Setup check completed! See the troubleshooting guide for more details.');

        return 0;
    }

    protected function checkOperatingSystem()
    {
        $os = PHP_OS;
        $osFamily = PHP_OS_FAMILY;

        $this->info("🖥️  Operating System: {$os} ({$osFamily})");

        switch ($osFamily) {
            case 'Darwin':
                $this->line('   ✅ macOS detected - Full support available');
                break;
            case 'Linux':
                $this->line('   ✅ Linux detected - Full support available');
                break;
            case 'Windows':
                $this->warn('   ⚠️  Windows detected - Limited support (mock mode recommended)');
                break;
            default:
                $this->error("   ❌ Unsupported OS: {$osFamily}");
        }

        $this->newLine();
    }

    protected function checkBluetoothAvailability()
    {
        $this->info('🔵 Checking Bluetooth availability...');

        if (PHP_OS_FAMILY === 'Darwin') {
            $this->checkMacOSBluetooth();
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $this->checkLinuxBluetooth();
        } else {
            $this->warn('   ⚠️  Cannot check Bluetooth on this OS');
        }

        $this->newLine();
    }

    protected function checkMacOSBluetooth()
    {
        try {
            $result = Process::run('system_profiler SPBluetoothDataType');

            if ($result->successful()) {
                $this->line('   ✅ Bluetooth system profiler accessible');

                // Check if blueutil is available
                $blueUtilResult = Process::run('which blueutil');
                if ($blueUtilResult->successful()) {
                    $this->line('   ✅ blueutil command available');
                } else {
                    $this->warn('   ⚠️  blueutil not found - install with: brew install blueutil');
                }
            } else {
                $this->error('   ❌ Cannot access Bluetooth system profiler');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error checking Bluetooth: ' . $e->getMessage());
        }
    }

    protected function checkLinuxBluetooth()
    {
        try {
            $result = Process::run('which bluetoothctl');

            if ($result->successful()) {
                $this->line('   ✅ bluetoothctl command available');

                // Check Bluetooth service
                $serviceResult = Process::run('systemctl is-active bluetooth');
                if ($serviceResult->successful() && trim($serviceResult->output()) === 'active') {
                    $this->line('   ✅ Bluetooth service is running');
                } else {
                    $this->warn('   ⚠️  Bluetooth service not running - start with: sudo systemctl start bluetooth');
                }
            } else {
                $this->error('   ❌ bluetoothctl not found - install BlueZ tools');
                $this->line('      Ubuntu/Debian: sudo apt-get install bluez bluez-tools');
                $this->line('      CentOS/RHEL: sudo yum install bluez bluez-tools');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error checking Bluetooth: ' . $e->getMessage());
        }
    }

    protected function checkConfiguration()
    {
        $this->info('⚙️  Checking configuration...');

        // Check if config file exists
        $configPath = config_path('printer.php');
        if (file_exists($configPath)) {
            $this->line('   ✅ Printer configuration file exists');
        } else {
            $this->error('   ❌ Printer configuration file not found');
        }

        // Check current config values
        $maxAttempts = config('printer.bluetooth.max_connection_attempts', 3);
        $retryDelay = config('printer.bluetooth.connection_retry_delay', 2);
        $this->line("   📋 Max connection attempts: {$maxAttempts}");
        $this->line("   📋 Retry delay: {$retryDelay} seconds");

        $this->newLine();
    }

    protected function provideInstructions()
    {
        $this->info('📋 Quick Start Guide:');
        $this->newLine();

        $this->line('1. 🔧 Setup your thermal printer:');
        $this->line('   - Turn on Bluetooth thermal printer');
        $this->line('   - Enable pairing mode (hold Bluetooth button 3-5 seconds)');
        $this->line('   - LED should blink blue when in pairing mode');
        $this->newLine();

        $this->line('2. 🖥️  Access Cashier Panel:');
        $this->line('   - Go to /cashier in your browser');
        $this->line('   - Navigate to Payment Processing');
        $this->line('   - Click "Scan Devices" to find printer');
        $this->line('   - Click "Printer Settings" to connect');
        $this->newLine();

        $this->line('3. 🧪 Test connection:');
        $this->line('   - Click "Test Printer" button');
        $this->line('   - Click "Diagnostics" if having issues');
        $this->newLine();

        $this->line('4. 🆘 If still not working:');
        $this->line('   - Check BLUETOOTH_PRINTER_TROUBLESHOOTING.md');
        $this->line('   - Set PRINTER_MOCK_ENABLED=true in .env for testing');
        $this->line('   - Check logs: tail -f storage/logs/laravel.log');
        $this->newLine();

        $this->comment('📚 For detailed troubleshooting, see: BLUETOOTH_PRINTER_TROUBLESHOOTING.md');
    }
}
