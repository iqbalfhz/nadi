<?php

namespace App\Providers;

use App\Settings\BackupSettings;
use Carbon\CarbonImmutable;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGoogleDriveDisk();
    }

    /**
     * Registers the 'google' disk driver lazily: nothing here runs until
     * Storage::disk('google') is actually resolved (i.e. when a backup
     * runs), so it's safe to register even before the account is set up.
     * Credentials come from BackupSettings (DB-backed), not config/env, so
     * the destination Google account can be swapped from Pengaturan >
     * Backup Otomatis in /admin without a code deploy.
     */
    protected function configureGoogleDriveDisk(): void
    {
        Storage::extend('google', function (): FilesystemAdapter {
            $settings = app(BackupSettings::class);

            $client = new GoogleClient;
            $client->setClientId($settings->client_id);
            $client->setClientSecret($settings->client_secret);
            $client->refreshToken($settings->refresh_token);
            $client->setApplicationName(config('app.name'));

            $adapter = new GoogleDriveAdapter(
                new GoogleDrive($client),
                $settings->folder !== '' ? $settings->folder : '/',
            );

            return new FilesystemAdapter(new Filesystem($adapter), $adapter);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
