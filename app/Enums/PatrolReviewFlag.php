<?php

namespace App\Enums;

/**
 * Reasons a patrol is worth a second look.
 *
 * Every one of these has an honest explanation, which is why none of them
 * refuses anything. They exist so a supervisor knows which handful of rounds
 * to look at out of a month of them — and, above all, whose photos to open.
 * The mandatory photo is the strongest control this module has; a control
 * nobody ever looks at is the same as no control.
 */
enum PatrolReviewFlag: string
{
    /**
     * Two different posts by the same guard, minutes apart at most.
     *
     * The one signal here that needs no map. Posts have no coordinates in
     * NADI, so a real "too far, too fast" check is not available — but
     * nobody is at two posts at once whatever the distance between them.
     */
    case ImpossibleTravel = 'impossible_travel';

    /**
     * The same post scanned again by the same guard within a few minutes.
     *
     * A double-tap, a retried submission the outbox already sent, or a
     * sticker photographed and scanned twice over. Usually harmless.
     */
    case RepeatedCheckpoint = 'repeated_checkpoint';

    /**
     * The handset claimed a time that had not happened yet.
     *
     * The only clock signal with no honest explanation — no shift pattern,
     * no basement, no queue delay produces a future timestamp. A large
     * *backward* gap is deliberately not flagged: that is just an outbox
     * coming home, which is the behaviour the whole offline design exists
     * to serve.
     */
    case ClockAhead = 'clock_ahead';

    public function label(): string
    {
        return match ($this) {
            self::ImpossibleTravel => __('Dua pos dalam waktu mustahil'),
            self::RepeatedCheckpoint => __('Pos sama dipindai berulang'),
            self::ClockAhead => __('Jam HP di depan waktu server'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ImpossibleTravel => 'danger',
            self::RepeatedCheckpoint => 'gray',
            self::ClockAhead => 'warning',
        };
    }

    /**
     * What a supervisor should actually do, written for the person reading
     * the panel rather than the person who wrote the check.
     */
    public function guidance(): string
    {
        return match ($this) {
            self::ImpossibleTravel => __('Buka foto kedua laporan. Kalau keduanya menunjukkan pos yang benar, kemungkinan besar dua petugas berbagi satu HP — bukan ronde palsu.'),
            self::RepeatedCheckpoint => __('Biasanya kiriman ulang dari outbox atau tombol tertekan dua kali. Cukup dilihat sekilas.'),
            self::ClockAhead => __('Jam di HP petugas perlu disetel ulang ke otomatis. Waktu laporannya sendiri sudah dikoreksi server.'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases()),
            'label',
            'value',
        );
    }
}
