<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorJPG;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BarcodeService
{
    protected $generator;
    protected $type;
    protected $width;
    protected $height;

    public function __construct($type = 'PNG', $width = 2, $height = 30)
    {
        $this->type = $type;
        $this->width = $width;
        $this->height = $height;
        
        switch (strtoupper($type)) {
            case 'SVG':
                $this->generator = new BarcodeGeneratorSVG();
                break;
            case 'JPG':
                $this->generator = new BarcodeGeneratorJPG();
                break;
            default:
                $this->generator = new BarcodeGeneratorPNG();
                break;
        }
    }

    /**
     * Generate barcode and save to storage
     */
    public function generateBarcode($code, $filename = null)
    {
        if (!$filename) {
            $filename = 'barcodes/' . Str::slug($code) . '_' . time() . '.' . strtolower($this->type);
        }

        try {
            // Generate barcode
            $barcodeData = $this->generator->getBarcode($code, $this->generator::TYPE_CODE_128, $this->width, $this->height);
            
            // Save to storage
            Storage::disk('public')->put($filename, $barcodeData);
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => Storage::disk('public')->url($filename),
                'code' => $code
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate barcode and return base64 data
     */
    public function generateBarcodeBase64($code)
    {
        try {
            $barcodeData = $this->generator->getBarcode($code, $this->generator::TYPE_CODE_128, $this->width, $this->height);
            
            return [
                'success' => true,
                'data' => base64_encode($barcodeData),
                'mime_type' => $this->getMimeType(),
                'code' => $code
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate barcode for printing (raw data)
     */
    public function generateBarcodeForPrint($code, $printerSettings = [])
    {
        $width = $printerSettings['width'] ?? $this->width;
        $height = $printerSettings['height'] ?? $this->height;
        
        try {
            $barcodeData = $this->generator->getBarcode($code, $this->generator::TYPE_CODE_128, $width, $height);
            
            return [
                'success' => true,
                'data' => $barcodeData,
                'code' => $code,
                'settings' => $printerSettings
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate multiple barcodes for batch printing
     */
    public function generateBatchBarcodes($codes, $printerSettings = [])
    {
        $results = [];
        
        foreach ($codes as $code) {
            $result = $this->generateBarcodeForPrint($code, $printerSettings);
            $results[] = $result;
        }
        
        return $results;
    }

    /**
     * Get supported barcode types
     */
    public static function getSupportedTypes()
    {
        return [
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
        ];
    }

    /**
     * Get MIME type for current generator
     */
    private function getMimeType()
    {
        switch (strtoupper($this->type)) {
            case 'SVG':
                return 'image/svg+xml';
            case 'JPG':
                return 'image/jpeg';
            default:
                return 'image/png';
        }
    }

    /**
     * Set barcode dimensions
     */
    public function setDimensions($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * Set barcode type
     */
    public function setType($type)
    {
        $this->type = $type;
        
        switch (strtoupper($type)) {
            case 'SVG':
                $this->generator = new BarcodeGeneratorSVG();
                break;
            case 'JPG':
                $this->generator = new BarcodeGeneratorJPG();
                break;
            default:
                $this->generator = new BarcodeGeneratorPNG();
                break;
        }
        
        return $this;
    }
}