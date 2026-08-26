<?php

namespace Tests\Feature;

use App\Enums\MessengerDeliveryStatus;
use App\Enums\QueueTicketStatus;
use App\Filament\Widgets\Dashboard\DocumentsByTypeChart;
use App\Filament\Widgets\Dashboard\MessengerStatusChart;
use App\Filament\Widgets\Dashboard\ModuleActivityChart;
use App\Filament\Widgets\Dashboard\QueueByCategoryChart;
use App\Filament\Widgets\Dashboard\RevenueChart;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\MessengerDelivery;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use App\Models\Ticket;
use App\Models\VendorSale;
use Filament\Widgets\ChartWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pinned mid-month so "7 Hari Terakhir" never straddles a month
        // boundary and the expected bucket labels below stay stable.
        $this->travelTo('2026-08-14 10:00:00');
    }

    public function test_the_activity_chart_places_each_record_on_its_own_day(): void
    {
        $this->actingAsSuperAdmin();

        Document::factory()->count(2)->create();
        Document::factory()->create(['created_at' => now()->subDays(2)]);

        $data = $this->chartData(ModuleActivityChart::class, ['period' => 'last_7_days']);

        $this->assertCount(7, $data['labels']);
        $this->assertSame(['08 Aug', '09 Aug', '10 Aug', '11 Aug', '12 Aug', '13 Aug', '14 Aug'], $data['labels']);

        $documents = $this->dataset($data, 'Dokumen');

        // Zero-filled: a quiet day is a dip, not a missing column that would
        // shift every later point one place to the left.
        $this->assertSame([0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 2.0], $documents);
    }

    public function test_the_activity_chart_collapses_long_ranges_into_coarser_buckets(): void
    {
        $this->actingAsSuperAdmin();

        Document::factory()->create();

        $weekly = $this->chartData(ModuleActivityChart::class, [
            'period' => 'custom',
            'startDate' => '2026-05-01',
            'endDate' => '2026-08-14',
        ]);

        // ~106 days would be an unreadable 106-column chart at daily
        // granularity, so it groups by week instead.
        $this->assertLessThan(30, count($weekly['labels']));
        $this->assertSame(array_sum($this->dataset($weekly, 'Dokumen')), 1.0);

        $monthly = $this->chartData(ModuleActivityChart::class, [
            'period' => 'custom',
            'startDate' => '2025-01-01',
            'endDate' => '2026-08-14',
        ]);

        $this->assertSame('Jan 2025', $monthly['labels'][0]);
        $this->assertSame('Aug 2026', end($monthly['labels']));
    }

    public function test_the_activity_chart_only_plots_modules_the_user_can_see(): void
    {
        $this->actingAsEmployeeWithPermissions('ViewAny:Document');

        Document::factory()->create();

        $data = $this->chartData(ModuleActivityChart::class, ['period' => 'today']);

        $this->assertSame(['Dokumen'], array_column($data['datasets'], 'label'));
    }

    public function test_the_activity_chart_counts_only_queue_tickets_that_were_actually_served(): void
    {
        $this->actingAsSuperAdmin();

        QueueTicket::factory()->create(['status' => QueueTicketStatus::Done]);
        QueueTicket::factory()->create(['status' => QueueTicketStatus::Waiting]);
        QueueTicket::factory()->create(['status' => QueueTicketStatus::Skipped]);

        $data = $this->chartData(ModuleActivityChart::class, ['period' => 'today']);

        $this->assertSame([1.0], $this->dataset($data, 'Antrian Dilayani'));
    }

    public function test_the_revenue_chart_stacks_both_pos_modules(): void
    {
        $this->actingAsSuperAdmin();

        Ticket::factory()->create(['price' => 25000]);
        VendorSale::factory()->create(['price' => 5000]);

        $data = $this->chartData(RevenueChart::class, ['period' => 'today']);

        $this->assertSame([25000.0], $this->dataset($data, 'Tiket Event'));
        $this->assertSame([5000.0], $this->dataset($data, 'Bazar Kios'));
    }

    public function test_the_revenue_chart_falls_back_to_its_empty_state_when_nothing_sold(): void
    {
        $this->actingAsSuperAdmin();

        // A row of flat zero-height bars reads as a broken chart; Filament's
        // empty state only kicks in when getData() is empty.
        $this->assertSame([], $this->chartData(RevenueChart::class, ['period' => 'today']));
    }

    public function test_the_queue_chart_breaks_the_period_down_per_counter(): void
    {
        $this->actingAsSuperAdmin();

        $cs = QueueCategory::factory()->create(['name' => 'Customer Service']);
        $kasir = QueueCategory::factory()->create(['name' => 'Kasir']);

        QueueTicket::factory()->count(3)->create(['queue_category_id' => $cs->id]);
        QueueTicket::factory()->create(['queue_category_id' => $kasir->id]);
        QueueTicket::factory()->create(['queue_category_id' => $kasir->id, 'created_at' => now()->subMonths(2)]);

        $data = $this->chartData(QueueByCategoryChart::class, ['period' => 'today']);

        $this->assertSame(['Customer Service', 'Kasir'], $data['labels']);
        $this->assertSame([3.0, 1.0], $data['datasets'][0]['data']);
    }

    public function test_the_queue_chart_is_empty_when_no_numbers_were_taken(): void
    {
        $this->actingAsSuperAdmin();

        QueueTicket::factory()->create(['created_at' => now()->subMonths(2)]);

        $this->assertSame([], $this->chartData(QueueByCategoryChart::class, ['period' => 'today']));
    }

    public function test_the_document_chart_lists_the_busiest_type_first(): void
    {
        $this->actingAsSuperAdmin();

        $memo = DocumentType::factory()->create(['name' => 'Memo']);
        $suratKeluar = DocumentType::factory()->create(['name' => 'Surat Keluar']);

        Document::factory()->create(['document_type_id' => $memo->id]);
        Document::factory()->count(4)->create(['document_type_id' => $suratKeluar->id]);

        $data = $this->chartData(DocumentsByTypeChart::class, ['period' => 'today']);

        $this->assertSame(['Surat Keluar', 'Memo'], $data['labels']);
        $this->assertSame([4.0, 1.0], $data['datasets'][0]['data']);
    }

    public function test_the_messenger_chart_follows_the_delivery_lifecycle_order(): void
    {
        $this->actingAsSuperAdmin();

        MessengerDelivery::factory()->create(['status' => MessengerDeliveryStatus::Delivered]);
        MessengerDelivery::factory()->count(2)->create(['status' => MessengerDeliveryStatus::Available]);

        $data = $this->chartData(MessengerStatusChart::class, ['period' => 'today']);

        // Enum order (Tersedia → Terkirim), not whatever order the database
        // happened to return, so the legend always reads as the lifecycle.
        $this->assertSame(['Tersedia', 'Terkirim'], $data['labels']);
        $this->assertSame([2.0, 1.0], $data['datasets'][0]['data']);
    }

    /**
     * @param  class-string<ChartWidget>  $widget
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function chartData(string $widget, array $filters): array
    {
        $instance = Livewire::test($widget, ['pageFilters' => $filters])->instance();

        /** @var array<string, mixed> $data */
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($instance);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, float>
     */
    private function dataset(array $data, string $label): array
    {
        foreach ($data['datasets'] ?? [] as $dataset) {
            if ($dataset['label'] === $label) {
                return $dataset['data'];
            }
        }

        $this->fail("No dataset labelled [{$label}] in the chart.");
    }
}
