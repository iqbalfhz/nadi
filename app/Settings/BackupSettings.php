<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class BackupSettings extends Settings
{
    public bool $enabled;

    public string $notify_email;

    public string $client_id;

    #[ShouldBeEncrypted]
    public string $client_secret;

    #[ShouldBeEncrypted]
    public string $refresh_token;

    public string $folder;

    public static function group(): string
    {
        return 'backup';
    }
}
