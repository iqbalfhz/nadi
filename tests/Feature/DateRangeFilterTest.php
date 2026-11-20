<?php

namespace Tests\Feature;

use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\QueueTickets\Pages\ListQueueTickets;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Dari / Sampai date filter every report table shares, as its chip reads.
 */
class DateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
    }

    public function test_a_today_default_reads_as_one_chip(): void
    {
        Livewire::test(ListQueueTickets::class)
            ->assertSee(__('Hari ini (:tanggal)', ['tanggal' => now()->translatedFormat('d M Y')]));
    }

    public function test_a_this_month_default_reads_as_one_chip(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(9));

        Livewire::test(ListDocuments::class)
            ->assertSee(__('Bulan ini (:bulan)', ['bulan' => now()->translatedFormat('M Y')]));
    }

    public function test_the_chip_follows_the_interface_language(): void
    {
        app()->setLocale('en');

        Livewire::test(ListQueueTickets::class)
            ->assertSee('Today ('.now()->translatedFormat('d M Y').')')
            ->assertDontSee('Hari ini');
    }
}
