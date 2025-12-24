<?php

namespace App\Helpers;

class NumberToWords
{
    private static $ones = [
        '', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE',
        'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN',
        'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'
    ];

    private static $tens = [
        '', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'
    ];

    private static $hundreds = [
        '', 'ONE HUNDRED', 'TWO HUNDRED', 'THREE HUNDRED', 'FOUR HUNDRED',
        'FIVE HUNDRED', 'SIX HUNDRED', 'SEVEN HUNDRED', 'EIGHT HUNDRED', 'NINE HUNDRED'
    ];

    public static function convert($number)
    {
        if ($number == 0) {
            return 'ZERO';
        }

        // Round to 2 decimal places
        $number = round($number, 2);
        
        // Handle decimal numbers
        $parts = explode('.', number_format($number, 2, '.', ''));
        $integerPart = (int)$parts[0];
        $decimalPart = isset($parts[1]) ? (int)$parts[1] : 0;

        $result = self::convertInteger($integerPart);

        if ($decimalPart > 0) {
            $result .= ' AND ' . self::convertInteger($decimalPart) . ' CENTS';
        }

        return $result;
    }

    private static function convertInteger($number)
    {
        if ($number == 0) {
            return '';
        }

        if ($number < 20) {
            return self::$ones[$number];
        }

        if ($number < 100) {
            $tensDigit = (int)($number / 10);
            $onesDigit = $number % 10;
            $result = self::$tens[$tensDigit];
            if ($onesDigit > 0) {
                $result .= ' ' . self::$ones[$onesDigit];
            }
            return $result;
        }

        if ($number < 1000) {
            $hundredsDigit = (int)($number / 100);
            $remainder = $number % 100;
            $result = self::$hundreds[$hundredsDigit];
            if ($remainder > 0) {
                $result .= ' AND ' . self::convertInteger($remainder);
            }
            return $result;
        }

        if ($number < 1000000) {
            $thousands = (int)($number / 1000);
            $remainder = $number % 1000;
            $result = self::convertInteger($thousands) . ' THOUSAND';
            if ($remainder > 0) {
                if ($remainder < 100) {
                    $result .= ' AND ' . self::convertInteger($remainder);
                } else {
                    $result .= ' ' . self::convertInteger($remainder);
                }
            }
            return $result;
        }

        if ($number < 1000000000) {
            $millions = (int)($number / 1000000);
            $remainder = $number % 1000000;
            $result = self::convertInteger($millions) . ' MILLION';
            if ($remainder > 0) {
                if ($remainder < 100) {
                    $result .= ' AND ' . self::convertInteger($remainder);
                } else {
                    $result .= ' ' . self::convertInteger($remainder);
                }
            }
            return $result;
        }

        return 'AMOUNT TOO LARGE';
    }
}

