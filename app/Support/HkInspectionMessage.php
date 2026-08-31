<?php

namespace App\Support;

use App\Models\HkInspection;

/**
 * Renders one inspection as the message that lands in the housekeeping
 * Telegram group.
 *
 * Kept apart from the job so the wording can be asserted directly in tests
 * without faking HTTP, and so changing the format never risks touching the
 * delivery logic.
 */
class HkInspectionMessage
{
    /**
     * Telegram truncates a photo caption at 1024 characters and rejects
     * anything longer outright, so the two free-text fields are clipped well
     * inside that. A supervisor's full text is always readable in /admin.
     */
    private const MAX_FREE_TEXT = 300;

    public static function for(HkInspection $inspection): string
    {
        $inspection->loadMissing(['category', 'area', 'user']);

        $lines = [
            '🧹 LAPORAN HOUSEKEEPING',
            '',
            self::row('Kategori', $inspection->category->name),
            self::row('Titik', $inspection->area->name),
        ];

        // Only categories flagged requires_floor collect this, so the row is
        // absent rather than blank everywhere else.
        if (filled($inspection->floor)) {
            $lines[] = self::row('Lantai', (string) $inspection->floor);
        }

        $lines[] = self::row('Kondisi', $inspection->condition->emoji().' '.$inspection->condition->label());
        $lines[] = self::row('Shift', $inspection->shift->label());
        $lines[] = self::row('Petugas', $inspection->staff_name);
        $lines[] = self::row('Pengawas', $inspection->user->name);
        $lines[] = self::row('Waktu', $inspection->created_at?->translatedFormat('d M Y H:i') ?? '-');

        if (filled($inspection->notes)) {
            $lines[] = '';
            $lines[] = 'Keterangan:';
            $lines[] = self::clip((string) $inspection->notes);
        }

        if (filled($inspection->follow_up)) {
            $lines[] = '';
            $lines[] = 'Tindak Lanjut:';
            $lines[] = self::clip((string) $inspection->follow_up);
        }

        return implode("\n", $lines);
    }

    /**
     * Padded so the values line up in a monospace-ish column on a phone,
     * which is how the manual WhatsApp reports were already written.
     */
    private static function row(string $label, string $value): string
    {
        return str_pad($label, 9).': '.$value;
    }

    private static function clip(string $text): string
    {
        return mb_strimwidth($text, 0, self::MAX_FREE_TEXT, '…');
    }
}
