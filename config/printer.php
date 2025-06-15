<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Thermal Printer Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Bluetooth thermal printer integration in the cashier system.
    | These settings control device scanning, connection, and printing behavior.
    |
    */

    'bluetooth' => [
        /*
        | Bluetooth scanning timeout in seconds
        */
        'scan_timeout' => env('PRINTER_SCAN_TIMEOUT', 10),

        /*
        | Maximum connection attempts before giving up
        */
        'max_connection_attempts' => env('PRINTER_MAX_CONNECTION_ATTEMPTS', 3),

        /*
        | Connection retry delay in seconds
        */
        'connection_retry_delay' => env('PRINTER_CONNECTION_RETRY_DELAY', 2),

        /*
        | Device scanning commands by OS
        */
        'scan_commands' => [
            'Darwin' => 'system_profiler SPBluetoothDataType -json',
            'Linux' => 'bluetoothctl devices',
            'Windows' => null, // Not implemented yet
        ],

        /*
        | Connection commands by OS
        */
        'connect_commands' => [
            'Darwin' => 'blueutil --connect {device}',
            'Linux' => 'bluetoothctl connect {device}',
            'Windows' => null, // Not implemented yet
        ],

        /*
        | Disconnect commands by OS
        */
        'disconnect_commands' => [
            'Darwin' => 'blueutil --disconnect {device}',
            'Linux' => 'bluetoothctl disconnect {device}',
            'Windows' => null, // Not implemented yet
        ],
    ],

    'devices' => [
        /*
        | Keywords to identify thermal printers
        */
        'thermal_printer_keywords' => [
            'thermal',
            'printer',
            'xprinter',
            'epson',
            'receipt',
            'pos',
            'tm-',
            'rp-',
            'bluetooth printer',
            'mini printer',
            'thermal printer',
            'pos printer',
        ],

        /*
        | Known thermal printer models and their configurations
        */
        'known_models' => [
            'xprinter' => [
                'name' => 'Xprinter Series',
                'paper_widths' => [58, 80],
                'default_width' => 58,
                'connection_timeout' => 5,
            ],
            'epson' => [
                'name' => 'Epson TM Series',
                'paper_widths' => [58, 80],
                'default_width' => 80,
                'connection_timeout' => 3,
            ],
            'thermal' => [
                'name' => 'Generic Thermal Printer',
                'paper_widths' => [58, 80],
                'default_width' => 58,
                'connection_timeout' => 5,
            ],
        ],
    ],

    'paper' => [
        /*
        | Supported paper widths and their character limits
        */
        'widths' => [
            58 => [
                'name' => '58mm (Standard)',
                'max_chars' => 32,
                'recommended' => true,
            ],
            80 => [
                'name' => '80mm (Wide)',
                'max_chars' => 48,
                'recommended' => false,
            ],
        ],

        /*
        | Default paper width
        */
        'default_width' => 58,
    ],

    'receipt' => [
        /*
        | Receipt formatting options
        */
        'formatting' => [
            'header_separator' => '=',
            'line_separator' => '-',
            'center_padding' => ' ',
        ],

        /*
        | Auto-print settings
        */
        'auto_print' => [
            'enabled' => env('PRINTER_AUTO_PRINT', true),
            'delay' => env('PRINTER_AUTO_PRINT_DELAY', 0), // seconds
        ],
    ],

    'diagnostics' => [
        /*
        | Enable detailed logging for troubleshooting
        */
        'detailed_logging' => env('PRINTER_DETAILED_LOGGING', true),

        /*
        | Log connection attempts and failures
        */
        'log_connections' => env('PRINTER_LOG_CONNECTIONS', true),

        /*
        | Log device scans
        */
        'log_scans' => env('PRINTER_LOG_SCANS', true),
    ],

    'mock' => [
        /*
        | Mock devices for testing when no real printers are available
        */
        'devices' => [
            'mock_printer_58' => 'Thermal Printer 58mm (Disconnected)',
            'mock_printer_80' => 'Thermal Printer 80mm (Disconnected)',
            'mock_xprinter' => 'Xprinter XP-58IIH (Disconnected)',
            'mock_epson' => 'Epson TM-T20III (Disconnected)',
        ],

        /*
        | Enable mock device simulation
        */
        'enabled' => env('PRINTER_MOCK_ENABLED', false),
    ],

    'troubleshooting' => [
        /*
        | Common issues and solutions
        */
        'common_issues' => [
            'no_devices_found' => [
                'problem' => 'No Bluetooth devices found during scan',
                'solutions' => [
                    'Make sure printer is turned on',
                    'Enable Bluetooth on the system',
                    'Put printer in pairing mode',
                    'Check if printer is already paired with another device',
                ],
            ],
            'connection_failed' => [
                'problem' => 'Cannot connect to printer',
                'solutions' => [
                    'Verify printer is on and in range',
                    'Check Bluetooth pairing status',
                    'Restart Bluetooth service',
                    'Try removing and re-pairing the device',
                ],
            ],
            'print_failed' => [
                'problem' => 'Connected but printing fails',
                'solutions' => [
                    'Check paper availability',
                    'Verify printer paper width setting',
                    'Test with a different document',
                    'Check printer error lights/status',
                ],
            ],
        ],
    ],
];
