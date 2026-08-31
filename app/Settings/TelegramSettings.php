<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class TelegramSettings extends Settings
{
    public bool $enabled;

    #[ShouldBeEncrypted]
    public string $bot_token;

    public string $chat_id;

    public static function group(): string
    {
        return 'telegram';
    }

    /**
     * Whether a send can even be attempted. Checked by the job before it
     * builds anything, so an unconfigured or switched-off integration costs
     * nothing and never queues a doomed retry.
     */
    public function isReady(): bool
    {
        return $this->enabled
            && filled($this->bot_token)
            && filled($this->chat_id);
    }

    public function endpoint(string $method): string
    {
        return "https://api.telegram.org/bot{$this->bot_token}/{$method}";
    }
}
