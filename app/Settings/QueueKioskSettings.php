<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class QueueKioskSettings extends Settings
{
    #[ShouldBeEncrypted]
    public string $pin;

    public bool $is_enabled;

    public static function group(): string
    {
        return 'queue_kiosk';
    }
}
