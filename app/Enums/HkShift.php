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
            self::Pagi => __('Pagi'),
            self::Siang => __('Siang'),
            self::Malam => __('Malam'),
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
