<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\Dashboard\DocumentsByTypeChart;
use App\Filament\Widgets\Dashboard\MessengerStatusChart;
use App\Filament\Widgets\Dashboard\ModuleActivityChart;
use App\Filament\Widgets\Dashboard\OperationalOverviewStats;
use App\Filament\Widgets\Dashboard\QueueByCategoryChart;
use App\Filament\Widgets\Dashboard\RevenueChart;
use App\Filament\Widgets\Dashboard\SalesOverviewStats;
use App\Filament\Widgets\QueueTicketsOverview;
use App\Filament\Widgets\TicketsOverview;
use App\Filament\Widgets\VendorSalesOverview;
use App\Filament\Widgets\VendorSettlementOverview;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_uses_our_own_dashboard_page_not_filaments_stock_one(): void
    {
        $this->assertContains(Dashboard::class, Filament::getPanel('admin')->getPages());
        $this->assertNotContains(\Filament\Pages\Dashboard::class, Filament::getPanel('admin')->getPages());
    }

    public function test_a_super_admin_can_open_the_dashboard(): void
    {
        $this->actingAsSuperAdmin();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // The widgets themselves are lazy-loaded (covered separately below),
        // but the period filter renders with the page — so this proves the
        // filter schema itself is wired up, not just that the route answers.
        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee('Periode Laporan');
    }

    public function test_every_dashboard_widget_renders_on_its_own(): void
    {
        // Widgets are lazy-loaded, so the page request above only proves the
        // placeholders render — mount each one directly to prove the real
        // Blade/Chart.js options behind them work too.
        $this->actingAsSuperAdmin();

        foreach ($this->registeredWidgets() as $widget) {
            Livewire::test($widget, ['pageFilters' => ['period' => 'this_month']])
                ->assertOk();
        }
    }

    public function test_the_dashboard_defaults_to_this_month(): void
    {
        $this->actingAsSuperAdmin();

        $filters = Livewire::test(Dashboard::class)->instance()->filters;

        $this->assertSame('this_month', $filters['period'] ?? null);
    }

    public function test_the_dashboard_shows_the_purpose_built_widgets(): void
    {
        $widgets = $this->registeredWidgets();

        foreach ([
            OperationalOverviewStats::class,
            SalesOverviewStats::class,
            ModuleActivityChart::class,
            RevenueChart::class,
            QueueByCategoryChart::class,
            DocumentsByTypeChart::class,
            MessengerStatusChart::class,
        ] as $widget) {
            $this->assertTrue($widgets->contains($widget), "{$widget} is missing from the dashboard.");
        }
    }

    public function test_filaments_stock_widgets_are_no_longer_on_the_dashboard(): void
    {
        $widgets = $this->registeredWidgets();

        $this->assertFalse($widgets->contains(AccountWidget::class));
        $this->assertFalse($widgets->contains(FilamentInfoWidget::class));
    }

    public function test_module_report_widgets_do_not_leak_onto_the_dashboard(): void
    {
        // Regression: these four live in the same directory discoverWidgets()
        // scans, which auto-registers every widget it finds onto the
        // Dashboard regardless of ->widgets([...]). They belong on their own
        // resource pages only — $isDiscovered = false is what keeps them off.
        $widgets = $this->registeredWidgets();

        $this->assertFalse($widgets->contains(QueueTicketsOverview::class));
        $this->assertFalse($widgets->contains(TicketsOverview::class));
        $this->assertFalse($widgets->contains(VendorSalesOverview::class));
        $this->assertFalse($widgets->contains(VendorSettlementOverview::class));
    }

    public function test_the_widgets_are_ordered_stats_first_then_charts(): void
    {
        $widgets = $this->registeredWidgets()->values();

        $this->assertSame(OperationalOverviewStats::class, $widgets->first());
        $this->assertSame(SalesOverviewStats::class, $widgets->get(1));
        $this->assertSame(ModuleActivityChart::class, $widgets->get(2));
    }

    public function test_a_user_with_no_module_permissions_sees_no_widgets_at_all(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ($this->registeredWidgets() as $widget) {
            $this->assertFalse($widget::canView(), "{$widget} should be hidden from a user with no permissions.");
        }
    }

    /**
     * @return Collection<int, class-string<Widget>>
     */
    private function registeredWidgets(): Collection
    {
        return collect(Filament::getPanel('admin')->getWidgets())
            ->map(fn (string|WidgetConfiguration $widget): string => is_string($widget) ? $widget : $widget->widget)
            ->values();
    }
}
