<?php

namespace App\Enums;

enum MessengerDeliveryStatus: string
{
    case Available = 'available';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Available => __('Tersedia'),
            self::PickedUp => __('Diambil Messenger'),
            self::InTransit => __('Dalam Perjalanan'),
            self::Delivered => __('Terkirim'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'gray',
            self::PickedUp => 'warning',
            self::InTransit => 'info',
            self::Delivered => 'success',
        };
    }
}
