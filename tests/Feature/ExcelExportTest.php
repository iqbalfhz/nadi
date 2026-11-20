<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Tickets\Pages\ListTickets as AppListTickets;
use App\Filament\App\Resources\VendorSales\Pages\ListVendorSales as AppListVendorSales;
use App\Filament\Resources\Tickets\Pages\ListTickets as AdminListTickets;
use App\Filament\Resources\VendorSales\Pages\ListVendorSales as AdminListVendorSales;
use App\Models\Ticket;
use App\Models\VendorSale;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * The Export Excel buttons end to end: the action runs and the file it hands
 * back is a spreadsheet that opens, with a heading row and the data under it.
 */
class ExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_ticket_report_exports(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
        Ticket::factory()->create();

        $rows = $this->exportedRows(AdminListTickets::class, 'penjualan-tiket-event');

        $this->assertCount(2, $rows);
        $this->assertContains(__('Nama Pembeli'), $rows[0]);
    }

    public function test_the_app_ticket_report_exports(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));
        $this->actingAsEmployeeWithPermissions('ViewAny:Ticket');
        Ticket::factory()->create();

        $rows = $this->exportedRows(AppListTickets::class, 'penjualan-tiket-event');

        $this->assertCount(2, $rows);
        $this->assertContains(__('Nama Pembeli'), $rows[0]);
    }

    public function test_the_admin_bazaar_report_exports(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
        VendorSale::factory()->create();

        $rows = $this->exportedRows(AdminListVendorSales::class, 'penjualan-bazar');

        $this->assertCount(2, $rows);
        $this->assertContains(__('Kios'), $rows[0]);
    }

    public function test_the_app_bazaar_report_exports(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));
        $this->actingAsEmployeeWithPermissions('ViewAny:VendorSale');
        VendorSale::factory()->create();

        $rows = $this->exportedRows(AppListVendorSales::class, 'penjualan-bazar');

        $this->assertCount(2, $rows);
        $this->assertContains(__('Kios'), $rows[0]);
    }

    public function test_both_panels_export_the_same_ticket_columns(): void
    {
        Ticket::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
        $admin = $this->exportedRows(AdminListTickets::class, 'penjualan-tiket-event')[0];

        Filament::setCurrentPanel(Filament::getPanel('app'));
        $this->actingAsEmployeeWithPermissions('ViewAny:Ticket');
        $app = $this->exportedRows(AppListTickets::class, 'penjualan-tiket-event')[0];

        $this->assertSame($admin, $app);
        $this->assertContains(__('No. Transaksi'), $app);
    }

    public function test_both_panels_export_the_same_bazaar_columns(): void
    {
        VendorSale::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
        $admin = $this->exportedRows(AdminListVendorSales::class, 'penjualan-bazar')[0];

        Filament::setCurrentPanel(Filament::getPanel('app'));
        $this->actingAsEmployeeWithPermissions('ViewAny:VendorSale');
        $app = $this->exportedRows(AppListVendorSales::class, 'penjualan-bazar')[0];

        $this->assertSame($admin, $app);
    }

    /**
     * @param  class-string  $page
     * @return array<int, array<int, mixed>>
     */
    private function exportedRows(string $page, string $filenamePrefix): array
    {
        /** @var Testable $component */
        $component = Livewire::test($page)
            ->callAction('export')
            ->assertFileDownloaded($filenamePrefix.'-'.now()->format('Y-m-d').'.xlsx');

        $path = (string) tempnam(sys_get_temp_dir(), 'nadi-export');
        file_put_contents($path, base64_decode((string) data_get($component->effects, 'download.content')));

        try {
            return IOFactory::load($path)->getActiveSheet()->toArray();
        } finally {
            unlink($path);
        }
    }
}
