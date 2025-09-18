<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Barcode Printer Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for barcode printing functionality.
    | You can customize printer settings, barcode types, and default values.
    |
    */

    'default' => [
        'type' => env('BARCODE_DEFAULT_TYPE', 'PNG'),
        'width' => env('BARCODE_DEFAULT_WIDTH', 2),
        'height' => env('BARCODE_DEFAULT_HEIGHT', 30),
    ],

    'printer' => [
        'enabled' => env('BARCODE_PRINTER_ENABLED', true),
        'driver' => env('BARCODE_PRINTER_DRIVER', 'thermal'), // thermal, laser, inkjet
        'width' => env('BARCODE_PRINTER_WIDTH', 58), // mm
        'height' => env('BARCODE_PRINTER_HEIGHT', 30), // mm
        'dpi' => env('BARCODE_PRINTER_DPI', 203),
    ],

    'supported_types' => [
        'PNG' => 'PNG Image',
        'SVG' => 'SVG Vector',
        'JPG' => 'JPEG Image',
    ],

    'barcode_types' => [
        'CODE_128' => 'Code 128',
        'CODE_39' => 'Code 39',
        'EAN_13' => 'EAN-13',
        'EAN_8' => 'EAN-8',
        'UPC_A' => 'UPC-A',
        'UPC_E' => 'UPC-E',
        'CODABAR' => 'Codabar',
        'MSI' => 'MSI',
        'POSTNET' => 'Postnet',
        'PLANET' => 'Planet',
        'RMS4CC' => 'RMS4CC',
        'KIX' => 'KIX',
        'IMB' => 'IMB',
        'CODE_11' => 'Code 11',
        'PHARMA_CODE' => 'Pharma Code',
        'PHARMA_CODE_2_TRACKS' => 'Pharma Code 2 Tracks'
    ],

    'dimensions' => [
        'width' => [
            'min' => 1,
            'max' => 10,
            'default' => 2
        ],
        'height' => [
            'min' => 10,
            'max' => 200,
            'default' => 30
        ]
    ],

    'storage' => [
        'disk' => env('BARCODE_STORAGE_DISK', 'public'),
        'path' => env('BARCODE_STORAGE_PATH', 'barcodes'),
        'cleanup_days' => env('BARCODE_CLEANUP_DAYS', 30),
    ],

    'print_settings' => [
        'auto_print' => env('BARCODE_AUTO_PRINT', false),
        'print_dialog' => env('BARCODE_PRINT_DIALOG', true),
        'print_preview' => env('BARCODE_PRINT_PREVIEW', true),
        'batch_size' => env('BARCODE_BATCH_SIZE', 50),
    ],

    'thermal_printer' => [
        'enabled' => env('THERMAL_PRINTER_ENABLED', false),
        'ip' => env('THERMAL_PRINTER_IP', '192.168.1.100'),
        'port' => env('THERMAL_PRINTER_PORT', 9100),
        'timeout' => env('THERMAL_PRINTER_TIMEOUT', 5),
        'commands' => [
            'init' => "\x1B\x40", // ESC @
            'cut' => "\x1D\x56\x00", // GS V 0
            'feed' => "\x0A", // LF
        ]
    ],

    'labels' => [
        'default_width' => 58, // mm
        'default_height' => 30, // mm
        'margin_top' => 2, // mm
        'margin_bottom' => 2, // mm
        'margin_left' => 2, // mm
        'margin_right' => 2, // mm
    ],

    'validation' => [
        'barcode_length' => [
            'min' => 3,
            'max' => 50
        ],
        'allowed_characters' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'prefix' => 'BC', // Default prefix for auto-generated barcodes
    ],

    'features' => [
        'auto_generate' => env('BARCODE_AUTO_GENERATE', true),
        'unique_check' => env('BARCODE_UNIQUE_CHECK', true),
        'batch_print' => env('BARCODE_BATCH_PRINT', true),
        'preview' => env('BARCODE_PREVIEW', true),
        'download' => env('BARCODE_DOWNLOAD', true),
        'history' => env('BARCODE_HISTORY', true),
    ],
];