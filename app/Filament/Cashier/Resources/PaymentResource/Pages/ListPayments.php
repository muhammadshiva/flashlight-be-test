<?php

namespace App\Filament\Cashier\Resources\PaymentResource\Pages;

use App\Filament\Cashier\Resources\PaymentResource;
use App\Services\ThermalPrinterService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printer_settings')
                ->label('Printer Settings')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\Select::make('printer_device')
                        ->label('Bluetooth Printer')
                        ->options(function () {
                            $printerService = new ThermalPrinterService();
                            return $printerService->getAvailablePrinters();
                        })
                        ->placeholder('Select printer device')
                        ->helperText('Available Bluetooth thermal printers')
                        ->reactive(),

                    \Filament\Forms\Components\Toggle::make('auto_print')
                        ->label('Auto Print Receipt')
                        ->default(true)
                        ->helperText('Automatically print receipt after payment completion'),

                    \Filament\Forms\Components\Select::make('printer_width')
                        ->label('Paper Width')
                        ->options([
                            58 => '58mm (Standard)',
                            80 => '80mm (Wide)',
                        ])
                        ->default(58)
                        ->helperText('Select the paper width of your thermal printer'),
                ])
                ->action(function (array $data) {
                    // Save printer settings
                    session(['printer_settings' => $data]);

                    // Try to connect to selected printer
                    if (!empty($data['printer_device'])) {
                        $printerService = new ThermalPrinterService();
                        if ($printerService->connectToPrinter($data['printer_device'])) {
                            \Filament\Notifications\Notification::make()
                                ->title('Printer Settings Saved')
                                ->body('Successfully connected to selected printer')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Connection Failed')
                                ->body('Settings saved but failed to connect to printer. Please check if the printer is on and paired.')
                                ->warning()
                                ->send();
                        }
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Printer Settings Saved')
                            ->success()
                            ->send();
                    }
                })
                ->modalHeading('Thermal Printer Settings')
                ->modalWidth('md'),

            Action::make('test_connection')
                ->label('Test Printer')
                ->icon('heroicon-o-signal')
                ->color('warning')
                ->action(function () {
                    $printerService = new ThermalPrinterService();

                    if ($printerService->testConnection()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Printer Connection Test')
                            ->body('Printer is connected and ready')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Printer Connection Failed')
                            ->body('Cannot connect to printer. Please check: 1) Printer is turned on, 2) Bluetooth is enabled, 3) Printer is paired')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('scan_devices')
                ->label('Scan Devices')
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray')
                ->action(function () {
                    try {
                        // Show scanning notification
                        \Filament\Notifications\Notification::make()
                            ->title('Scanning for Bluetooth Devices')
                            ->body('Searching for available thermal printers...')
                            ->info()
                            ->send();

                        $printerService = new ThermalPrinterService();
                        $devices = $printerService->scanBluetoothDevices();

                        if (!empty($devices)) {
                            $deviceCount = count($devices);
                            $deviceList = '';
                            foreach ($devices as $id => $name) {
                                $deviceList .= "• $name\n";
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Found $deviceCount Thermal Printer(s)")
                                ->body("Available devices:\n$deviceList\nGo to Printer Settings to connect.")
                                ->success()
                                ->duration(10000) // Show for 10 seconds
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('No Thermal Printers Found')
                                ->body('Please make sure: 1) Printer is turned on, 2) Bluetooth is enabled, 3) Printer is in pairing mode')
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Scan Failed')
                            ->body('Error while scanning: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('diagnostics')
                ->label('Diagnostics')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('secondary')
                ->action(function () {
                    $printerService = new ThermalPrinterService();
                    $diagnostics = $printerService->getConnectionDiagnostics();

                    $diagnosticInfo = "System Information:\n";
                    $diagnosticInfo .= "• OS: {$diagnostics['os']} ({$diagnostics['os_family']})\n";
                    $diagnosticInfo .= "• Selected Device: " . ($diagnostics['device'] ?? 'None') . "\n";
                    $diagnosticInfo .= "• Connection Status: " . ($diagnostics['connected'] ? 'Connected' : 'Disconnected') . "\n";
                    $diagnosticInfo .= "• Connection Attempts: {$diagnostics['attempts']}/{$diagnostics['max_attempts']}\n";

                    // Add session info
                    $settings = session('printer_settings', []);
                    if (!empty($settings)) {
                        $diagnosticInfo .= "\nSaved Settings:\n";
                        $diagnosticInfo .= "• Printer: " . ($settings['printer_device'] ?? 'Not set') . "\n";
                        $diagnosticInfo .= "• Paper Width: " . ($settings['printer_width'] ?? 'Not set') . "mm\n";
                        $diagnosticInfo .= "• Auto Print: " . ($settings['auto_print'] ? 'Enabled' : 'Disabled') . "\n";
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Printer Diagnostics')
                        ->body($diagnosticInfo)
                        ->info()
                        ->duration(15000) // Show for 15 seconds
                        ->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Payment Processing';
    }
}
