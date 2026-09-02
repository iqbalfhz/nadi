<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Security;
use App\Filament\Widgets\Dashboard\DocumentsByTypeChart;
use App\Filament\Widgets\Dashboard\MessengerStatusChart;
use App\Filament\Widgets\Dashboard\ModuleActivityChart;
use App\Filament\Widgets\Dashboard\OperationalOverviewStats;
use App\Filament\Widgets\Dashboard\QueueByCategoryChart;
use App\Filament\Widgets\Dashboard\RevenueChart;
use App\Filament\Widgets\Dashboard\SalesOverviewStats;
use App\Http\Middleware\SetInterfaceLanguage;
use App\Providers\Filament\Concerns\HasImpersonationBanner;
use App\Providers\Filament\Concerns\HasLanguageSwitcher;
use App\Providers\Filament\Concerns\HasNadiSidebarCustomizations;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    use HasImpersonationBanner, HasLanguageSwitcher, HasNadiSidebarCustomizations;

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
            // App\Filament\Pages\Dashboard, not Filament's own — it adds the
            // period filter every dashboard widget reads.
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // Listed explicitly (rather than left to discovery alone) so the
            // dashboard's composition is readable in one place; ->widgets()
            // and discovery are deduplicated by Filament, and the visual
            // order comes from each widget's own $sort. Filament's stock
            // AccountWidget/FilamentInfoWidget are deliberately gone — they
            // showed framework branding and a logout button the user menu
            // already provides, not information about the office.
            ->widgets([
                OperationalOverviewStats::class,
                SalesOverviewStats::class,
                ModuleActivityChart::class,
                RevenueChart::class,
                QueueByCategoryChart::class,
                DocumentsByTypeChart::class,
                MessengerStatusChart::class,
            ])
            // Explicit order + icon per group, grouped in tiers: (1) the core
            // operational modules from docs/NADI.MD, in that doc's own
            // numbering order; (2) standalone POS/sales features built
            // outside NADI.MD; (3) generic office tools; (4) administration
            // (who has access, then their roles/permissions, then
            // system-wide settings). Without this, Filament falls back to
            // resource-discovery order, which is arbitrary and was the whole
            // reason this sidebar felt disorganized.
            ->navigationGroups([
                NavigationGroup::make('Penomoran Dokumen')->icon(Heroicon::OutlinedDocumentDuplicate),
                NavigationGroup::make('Booking Room')->icon(Heroicon::OutlinedCalendarDays),
                NavigationGroup::make('Antrian')->icon(Heroicon::OutlinedQueueList),
                NavigationGroup::make('OB')->icon(Heroicon::OutlinedClipboardDocumentCheck),
                NavigationGroup::make('Security')->icon(Heroicon::OutlinedShieldCheck),
                NavigationGroup::make('HK')->icon(Heroicon::OutlinedSparkles),
                NavigationGroup::make('Messenger')->icon(Heroicon::OutlinedTruck),
                NavigationGroup::make('Tiket Event')->icon(Heroicon::OutlinedTicket),
                NavigationGroup::make('Bazar')->icon(Heroicon::OutlinedBuildingStorefront),
                NavigationGroup::make('Tools')->icon(Heroicon::OutlinedWrenchScrewdriver),
                NavigationGroup::make('Pengguna')->icon(Heroicon::OutlinedUsers),
                // No ->icon() override — this only controls where the
                // Shield plugin's own group lands in the order, not its
                // appearance. Mixing bare strings into this array (rather
                // than NavigationGroup instances) throws at render time.
                NavigationGroup::make('Filament Shield'),
                NavigationGroup::make('Sistem')->icon(Heroicon::OutlinedCog6Tooth),
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
            // shell (not the simple centered layout) so it feels native. Two-factor
            // settings sit beside it on App\Filament\Pages\Security, linked below.
            ->profile(isSimple: false)
            ->userMenuItems([
                Action::make('security')
                    ->label('Keamanan')
                    ->icon(Heroicon::ShieldCheck)
                    ->url(fn (): string => Security::getUrl()),
            ])
            ->authMiddleware([
                Authenticate::class,
                // After Authenticate: the choice lives on the account, so
                // there is nobody to ask before this point.
                SetInterfaceLanguage::class,
            ]);

        return $this->withLanguageSwitcher(
            $this->withImpersonationBanner(
                $this->withNadiSidebarCustomizations($panel),
            ),
        );
    }
}
