<?php

namespace Tests\Feature;

use App\Filament\Widgets\Dashboard\OperationalOverviewStats;
use App\Filament\Widgets\Dashboard\SalesOverviewStats;
use App\Models\Bazaar;
use App\Models\Document;
use App\Models\Event;
use App\Models\RoomBooking;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class DashboardOverviewStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_only_records_inside_the_selected_period(): void
    {
        $this->actingAsSuperAdmin();

        Document::factory()->count(2)->create();
        Document::factory()->create(['created_at' => now()->subDays(40)]);

        $this->assertSame('2', $this->statValue(OperationalOverviewStats::class, 'Dokumen Terbit', ['period' => 'today']));
    }

    public function test_the_trend_compares_against_the_previous_equally_long_window(): void
    {
        $this->actingAsSuperAdmin();

        // Period "today" ⇒ baseline is yesterday: 3 vs 2 is +50%.
        Document::factory()->count(3)->create();
        Document::factory()->count(2)->create(['created_at' => now()->subDay()]);

        $stat = $this->stat(OperationalOverviewStats::class, 'Dokumen Terbit', ['period' => 'today']);

        $this->assertSame('+50,0% vs periode sebelumnya', $stat->getDescription());
    }

    public function test_a_drop_against_the_previous_window_is_reported_as_a_decline(): void
    {
        $this->actingAsSuperAdmin();

        Document::factory()->create();
        Document::factory()->count(4)->create(['created_at' => now()->subDay()]);

        $stat = $this->stat(OperationalOverviewStats::class, 'Dokumen Terbit', ['period' => 'today']);

        $this->assertSame('−75,0% vs periode sebelumnya', $stat->getDescription());
        $this->assertSame('danger', $stat->getDescriptionColor());
    }

    public function test_a_first_ever_period_reads_as_no_comparison_rather_than_infinite_growth(): void
    {
        $this->actingAsSuperAdmin();

        Document::factory()->create();

        $stat = $this->stat(OperationalOverviewStats::class, 'Dokumen Terbit', ['period' => 'today']);

        $this->assertSame('Baru ada aktivitas periode ini', $stat->getDescription());
        $this->assertSame('gray', $stat->getDescriptionColor());
    }

    public function test_room_bookings_are_counted_by_when_the_room_is_used_not_when_it_was_booked(): void
    {
        $this->actingAsSuperAdmin();

        RoomBooking::factory()->create([
            'created_at' => now()->subMonths(2),
            'starts_at' => now()->setTime(9, 0),
            'ends_at' => now()->setTime(10, 0),
        ]);

        // Booked long ago, but the room is used today — a facility report is
        // about the latter.
        $this->assertSame('1', $this->statValue(OperationalOverviewStats::class, 'Booking Ruangan', ['period' => 'today']));
    }

    public function test_a_card_only_appears_for_a_module_the_user_can_actually_see(): void
    {
        $this->actingAsEmployeeWithPermissions('ViewAny:Document');

        Document::factory()->create();
        RoomBooking::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);

        $labels = $this->statLabels(OperationalOverviewStats::class, ['period' => 'today']);

        $this->assertSame(['Dokumen Terbit'], $labels);
    }

    public function test_the_operational_widget_is_hidden_from_a_user_without_any_module_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertFalse(OperationalOverviewStats::canView());
    }

    public function test_the_sales_widget_sums_revenue_for_the_selected_period(): void
    {
        $this->actingAsSuperAdmin();

        $event = Event::factory()->create();
        Ticket::factory()->create(['event_id' => $event->id, 'price' => 25000]);
        Ticket::factory()->create(['event_id' => $event->id, 'price' => 15000]);
        Ticket::factory()->create(['event_id' => $event->id, 'price' => 99000, 'created_at' => now()->subDays(3)]);

        $this->assertSame('Rp 40.000', $this->statValue(SalesOverviewStats::class, 'Pendapatan Tiket Event', ['period' => 'today']));
        $this->assertSame('2', $this->statValue(SalesOverviewStats::class, 'Tiket Terjual', ['period' => 'today']));
    }

    public function test_bazaar_transactions_are_counted_per_receipt_not_per_line_item(): void
    {
        $this->actingAsSuperAdmin();

        $bazaar = Bazaar::factory()->create();
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);

        // One cart, two line items — one transaction, Rp 30.000 total.
        foreach ([10000, 20000] as $price) {
            VendorSale::factory()->create([
                'bazaar_id' => $bazaar->id,
                'vendor_id' => $vendor->id,
                'vendor_product_id' => $product->id,
                'price' => $price,
                'transaction_number' => 'BZR-SHARED',
            ]);
        }

        $this->assertSame('1', $this->statValue(SalesOverviewStats::class, 'Transaksi Bazar', ['period' => 'today']));
        $this->assertSame('Rp 30.000', $this->statValue(SalesOverviewStats::class, 'Pendapatan Bazar', ['period' => 'today']));
    }

    public function test_total_revenue_rolls_up_both_pos_modules(): void
    {
        $this->actingAsSuperAdmin();

        Ticket::factory()->create(['price' => 25000]);
        VendorSale::factory()->create(['price' => 5000]);

        $this->assertSame('Rp 30.000', $this->statValue(SalesOverviewStats::class, 'Total Pendapatan', ['period' => 'today']));
    }

    public function test_the_combined_total_is_dropped_when_only_one_pos_module_is_visible(): void
    {
        // With one module visible it would just repeat the card beside it.
        $this->actingAsEmployeeWithPermissions('ViewAny:Ticket');

        Ticket::factory()->create(['price' => 25000]);

        $labels = $this->statLabels(SalesOverviewStats::class, ['period' => 'today']);

        $this->assertSame(['Pendapatan Tiket Event', 'Tiket Terjual'], $labels);
    }

    public function test_the_sales_widget_is_hidden_from_an_admin_without_either_pos_module(): void
    {
        $this->actingAsEmployeeWithPermissions('ViewAny:Document');

        $this->assertFalse(SalesOverviewStats::canView());
        $this->assertTrue(OperationalOverviewStats::canView());
    }

    /**
     * @param  class-string<StatsOverviewWidget>  $widget
     * @param  array<string, mixed>  $filters
     * @return array<int, Stat>
     */
    private function stats(string $widget, array $filters): array
    {
        $instance = Livewire::test($widget, ['pageFilters' => $filters])->instance();

        /** @var array<int, Stat> $stats */
        $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($instance);

        return $stats;
    }

    /**
     * @param  class-string<StatsOverviewWidget>  $widget
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    private function statLabels(string $widget, array $filters): array
    {
        return array_map(
            fn (Stat $stat): string => (string) $stat->getLabel(),
            $this->stats($widget, $filters),
        );
    }

    /**
     * @param  class-string<StatsOverviewWidget>  $widget
     * @param  array<string, mixed>  $filters
     */
    private function stat(string $widget, string $label, array $filters): Stat
    {
        foreach ($this->stats($widget, $filters) as $stat) {
            if ((string) $stat->getLabel() === $label) {
                return $stat;
            }
        }

        $this->fail("No stat labelled [{$label}] on [{$widget}].");
    }

    /**
     * @param  class-string<StatsOverviewWidget>  $widget
     * @param  array<string, mixed>  $filters
     */
    private function statValue(string $widget, string $label, array $filters): string
    {
        return (string) $this->stat($widget, $label, $filters)->getValue();
    }
}
