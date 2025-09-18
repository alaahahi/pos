<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\BarcodeService;

/**
 * @method static array generateBarcode(string $code, string $filename = null)
 * @method static array generateBarcodeBase64(string $code)
 * @method static array generateBarcodeForPrint(string $code, array $printerSettings = [])
 * @method static array generateBatchBarcodes(array $codes, array $printerSettings = [])
 * @method static array getSupportedTypes()
 * @method static BarcodeService setDimensions(int $width, int $height)
 * @method static BarcodeService setType(string $type)
 */
class Barcode extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'barcode';
    }

    /**
     * Create a new instance of the BarcodeService
     *
     * @return BarcodeService
     */
    public static function create($type = 'PNG', $width = 2, $height = 30)
    {
        return new BarcodeService($type, $width, $height);
    }
}
