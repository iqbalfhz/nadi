<?php

namespace App\Filament\Schemas;

use Filament\Infolists\Components\TextEntry;

/**
 * The two times an offline field report carries, for a detail page.
 *
 * The list deliberately shows only one of them: a supervisor scanning rows
 * wants "when was this done", and a second timestamp beside it only raises
 * the question of which one is real (see FieldReportTable).
 *
 * A detail page is the opposite situation. The reader opened this one record
 * on purpose, usually to work out whether it can be trusted — and there the
 * gap between "the worker says 03:15" and "the server heard at 07:02" is the
 * most useful thing on the page.
 */
class FieldReportEntries
{
    /**
     * When the worker pressed Kirim.
     *
     * Empty rather than falling back to created_at, unlike the list column.
     * On a detail page the two sit side by side, so showing the arrival time
     * twice would read as corroboration that isn't there — a blank says
     * plainly that no claimed time was recorded.
     */
    public static function reportedAt(): TextEntry
    {
        return TextEntry::make('submitted_at')
            ->label(__('Dilaporkan Petugas'))
            ->dateTime('d M Y H:i:s')
            ->placeholder(__('Tidak dicatat — laporan ini dibuat sebelum aplikasi mengirim waktunya'));
    }

    public static function receivedAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('Diterima Server'))
            ->dateTime('d M Y H:i:s');
    }

    /**
     * What the handset claimed before the server pulled it back into range.
     *
     * Hidden on an ordinary report, which is nearly all of them. When it does
     * appear, it is the one clock signal with no honest explanation: a time
     * that had not happened yet. See FieldReportTime::claimed().
     */
    public static function claimedAt(): TextEntry
    {
        return TextEntry::make('submitted_at_claimed')
            ->label(__('Diklaim HP'))
            ->dateTime('d M Y H:i:s')
            ->badge()
            ->color('warning')
            ->helperText(__('Jam di HP petugas tidak wajar, jadi waktunya dikoreksi server. Laporannya sendiri tetap tersimpan.'))
            ->visible(fn ($record): bool => $record->submitted_at_claimed !== null);
    }
}
