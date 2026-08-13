<?php

namespace App\Providers\Filament;

use App\Filament\App\Widgets\BookingCalendarWidget;
use App\Providers\Filament\Concerns\HasNadiSidebarCustomizations;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    use HasNadiSidebarCustomizations;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('app')
            ->path('app')
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/app/theme.css')
            ->favicon(asset('images/nadi-icon.png'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->widgets([
                BookingCalendarWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ]);

        return $this->withNadiSidebarCustomizations($panel);
    }
}
