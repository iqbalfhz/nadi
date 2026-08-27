<?php

namespace App\Support;

/**
 * Turns one logged attribute value into something readable in the activity
 * detail modal. Raw values come straight out of the database, so booleans
 * arrive as 1/0, nulls as nothing at all, and casts as arrays.
 */
class ActivityValue
{
    public static function render(mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '—',
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            is_array($value) => implode(', ', array_map(self::render(...), $value)),
            default => (string) $value,
        };
    }
}
