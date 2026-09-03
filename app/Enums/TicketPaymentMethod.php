<?php

namespace App\Enums;

enum TicketPaymentMethod: string
{
    case Cash = 'cash';
    case Qris = 'qris';
    case Edc = 'edc';

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('Tunai'),
            self::Qris => __('QRIS'),
            self::Edc => __('EDC'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::Qris => 'info',
            self::Edc => 'warning',
        };
    }
}
