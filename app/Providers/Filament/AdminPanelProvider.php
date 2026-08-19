<?php

namespace App\Providers\Filament;

use App\Providers\Filament\Concerns\HasNadiSidebarCustomizations;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    use HasNadiSidebarCustomizations;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->sidebarCollapsibleOnDesktop()
            ->favicon(asset('images/nadi-icon.png'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            // Quick-launch shortcuts to the Antrian module's non-Filament screens
            // (public kiosk/display routes, and the operator page which lives in
            // the /app panel) — open in a new tab so the admin session stays put.
            ->navigationItems([
                NavigationItem::make('Buka Kiosk (Ambil Nomor)')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->url('/antrian/kiosk-pin')
                    ->openUrlInNewTab()
                    ->group('Antrian')
                    ->sort(-3)
                    ->visible(fn (): bool => Auth::user()?->can('View:ManageQueueKioskSettings') ?? false),
                NavigationItem::make('Buka Layar Display (TV)')
                    ->icon(Heroicon::OutlinedTv)
                    ->url('/antrian/layar')
                    ->openUrlInNewTab()
                    ->group('Antrian')
                    ->sort(-2)
                    ->visible(fn (): bool => Auth::user()?->can('View:ManageQueueKioskSettings') ?? false),
                NavigationItem::make('Buka Halaman Operator')
                    ->icon(Heroicon::OutlinedSpeakerWave)
                    ->url('/app/queue-operator')
                    ->openUrlInNewTab()
                    ->group('Antrian')
                    ->sort(-1)
                    ->visible(fn (): bool => Auth::user()?->can('View:QueueOperator') ?? false),
            ])
            // Filament's own name/email/password page, rendered inside the panel
            // shell (not the simple centered layout) so it feels native. 2FA and
            // passkeys still live on the shared Fortify security page, linked
            // below, since those aren't covered by this page.
            ->profile(isSimple: false)
            ->userMenuItems([
                Action::make('security')
                    ->label('Keamanan')
                    ->icon(Heroicon::ShieldCheck)
                    ->url(fn (): string => route('security.edit')),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        return $this->withNadiSidebarCustomizations($panel);
    }
}
