<?php

namespace App\Enums;

enum QueueTicketStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case Done = 'done';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => __('Menunggu'),
            self::Called => __('Dipanggil'),
            self::Done => __('Selesai'),
            self::Skipped => __('Dilewati'),
        };
    }
}
