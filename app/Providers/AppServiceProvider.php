<?php

namespace App\Providers;

use App\Listeners\LogAccessActivity;
use App\Listeners\RecordBackupOutcome;
use App\Settings\BackupSettings;
use App\Support\Impersonation;
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

        // The nightly backup only reports failures by email, and this
        // installation sends mail to the log file. This keeps the outcome
        // somewhere the settings page can show it.
        Event::subscribe(RecordBackupOutcome::class);
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
            $properties = $activity->properties ?? collect();

            // Two stamps with two different preconditions, so two different
            // guards. Lumping them behind one check would silently drop the
            // impersonation mark anywhere the IP happens to be unavailable.

            // Console runs (scheduler, seeders, artisan) have no request and
            // so no meaningful IP — leave it off rather than record a
            // misleading one.
            if (! app()->runningInConsole()) {
                $properties = $properties->put('ip', request()->ip());
            }

            // Impersonation is a property of the session, not the request.
            // Without this, anything an admin changes while impersonating is
            // recorded as the employee having done it — an audit trail that
            // quietly lies is worse than none at all. The entry still belongs
            // to the employee, because that is whose account acted; this names
            // the hand that was actually on the keyboard.
            $impersonator = Impersonation::isActive() ? Impersonation::impersonator() : null;

            if ($impersonator !== null) {
                $properties = $properties->put('impersonated_by', [
                    'id' => $impersonator->getKey(),
                    'name' => $impersonator->name,
                ]);
            }

            $activity->properties = $properties;
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
