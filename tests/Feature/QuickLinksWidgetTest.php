<?php

namespace Tests\Feature;

use App\Filament\App\Widgets\QuickLinksWidget;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickLinksWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_it_only_shows_links_the_user_has_permission_for(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellTicket');

        $labels = collect((new QuickLinksWidget)->getLinks())->pluck('label');

        $this->assertTrue($labels->contains('Jual Tiket Event'));
        $this->assertFalse($labels->contains('Jual Produk Bazar'));
        $this->assertFalse($labels->contains('Booking Room'));
    }

    public function test_it_shows_multiple_links_when_the_user_has_multiple_permissions(): void
    {
        $this->actingAsEmployeeWithPermissions(['View:SellTicket', 'View:SellVendorProduct', 'ViewAny:RoomBooking']);

        $labels = collect((new QuickLinksWidget)->getLinks())->pluck('label');

        $this->assertTrue($labels->contains('Jual Tiket Event'));
        $this->assertTrue($labels->contains('Jual Produk Bazar'));
        $this->assertTrue($labels->contains('Booking Room'));
    }

    public function test_it_is_empty_when_the_users_only_permission_has_no_quick_link(): void
    {
        // ViewAny:Ticket (the report) deliberately has no quick-link card —
        // only "action" pages do, report/history lists stay reachable via
        // the sidebar instead.
        $this->actingAsEmployeeWithPermissions('ViewAny:Ticket');

        $this->assertSame([], (new QuickLinksWidget)->getLinks());
    }

    public function test_super_admins_can_see_the_dashboard_with_every_link(): void
    {
        $this->actingAsSuperAdmin();

        $labels = collect((new QuickLinksWidget)->getLinks())->pluck('label');

        $this->assertCount(10, $labels);
    }
}
