<?php

namespace App\Enums;

enum HkCondition: string
{
    case Bersih = 'bersih';
    case PerluPerbaikan = 'perlu_perbaikan';
    case Kotor = 'kotor';

    public function label(): string
    {
        return match ($this) {
            self::Bersih => 'Bersih',
            self::PerluPerbaikan => 'Perlu Perbaikan',
            self::Kotor => 'Kotor',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bersih => 'success',
            self::PerluPerbaikan => 'warning',
            self::Kotor => 'danger',
        };
    }

    /**
     * Read at a glance in a Telegram group on a phone, where the colour a
     * badge would carry in the panel isn't available.
     */
    public function emoji(): string
    {
        return match ($this) {
            self::Bersih => '✅',
            self::PerluPerbaikan => '⚠️',
            self::Kotor => '❌',

        };
    }

    /**
     * The single source of truth for "this report needs a follow-up action",
     * used by the form (to show and require the field) and by the tests.
     *
     * Deliberately keyed off the finding rather than the category: a clean
     * Public Area needs no follow-up either, while a dirty toilet plainly
     * does. It also stops a supervisor reporting "Kotor" and walking away
     * without saying what was done about it.
     */
    public function needsFollowUp(): bool
    {
        return $this !== self::Bersih;
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
