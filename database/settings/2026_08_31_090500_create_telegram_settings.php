<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Left disabled/empty by default — filled in via Pengaturan >
        // Telegram in /admin. bot_token is pre-encrypted here because
        // TelegramSettings marks it #[ShouldBeEncrypted]; the migrator writes
        // the raw payload directly, bypassing the settings object's own cast.
        $this->migrator->add('telegram.enabled', false);
        $this->migrator->add('telegram.bot_token', encrypt(''));
        $this->migrator->add('telegram.chat_id', '');
    }
};
