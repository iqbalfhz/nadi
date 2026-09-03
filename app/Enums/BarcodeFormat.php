<?php

namespace App\Enums;

enum BarcodeFormat: string
{
    case Qr = 'qr';
    case Code128 = 'code128';
    case Ean13 = 'ean13';
    case Code39 = 'code39';

    public function label(): string
    {
        return match ($this) {
            self::Qr => __('QR Code'),
            self::Code128 => __('Barcode (Code 128)'),
            self::Ean13 => __('Barcode (EAN-13)'),
            self::Code39 => __('Barcode (Code 39)'),
        };
    }
}
