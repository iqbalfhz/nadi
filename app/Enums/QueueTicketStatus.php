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
            self::Waiting => 'Menunggu',
            self::Called => 'Dipanggil',
            self::Done => 'Selesai',
            self::Skipped => 'Dilewati',
        };
    }
}
