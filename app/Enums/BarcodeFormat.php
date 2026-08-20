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
            self::Qr => 'QR Code',
            self::Code128 => 'Barcode (Code 128)',
            self::Ean13 => 'Barcode (EAN-13)',
            self::Code39 => 'Barcode (Code 39)',
        };
    }
}
