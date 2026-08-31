<?php

namespace App\Enums;

enum HkShift: string
{
    case Pagi = 'pagi';
    case Siang = 'siang';
    case Malam = 'malam';

    public function label(): string
    {
        return match ($this) {
            self::Pagi => 'Pagi',
            self::Siang => 'Siang',
            self::Malam => 'Malam',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pagi => 'warning',
            self::Siang => 'info',
            self::Malam => 'gray',
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
