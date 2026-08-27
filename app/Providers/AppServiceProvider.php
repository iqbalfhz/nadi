<?php

namespace App\Providers;

use App\Listeners\LogAccessActivity;
use App\Settings\BackupSettings;
use Carbon\CarbonImmutable;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;
use Spatie\Activitylog\Models\Activity;

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
        $this->configureActivityLog();
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
     * Wires up the Riwayat Aktivitas feature.
     *
     * Model edits log themselves through App\Concerns\LogsNadiActivity. The
     * two things that can't work that way are handled here: access events
     * (logins, role grants) have no model event to hang off, and the client
     * IP has to be stamped onto every entry regardless of where it came
     * from — an audit trail that can't say *from where* is half a trail.
     */
    protected function configureActivityLog(): void
    {
        Event::subscribe(LogAccessActivity::class);

        Activity::creating(function (Activity $activity): void {
            // Console runs (scheduler, seeders, artisan) have no request and
            // so no meaningful IP — leave it off rather than record a
            // misleading one.
            if (app()->runningInConsole()) {
                return;
            }

            $properties = $activity->properties ?? collect();

            $activity->properties = $properties->put('ip', request()->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Cloudflare Tunnel terminates TLS at the edge and forwards plain
        // HTTP to the container, so Laravel sees an insecure request and
        // generates http:// asset/URLs — browsers then block them as mixed
        // content on the https:// page. Force https since production is
        // never served any other way.
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

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
