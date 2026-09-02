<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Security as AppSecurity;
use App\Filament\Pages\Security as AdminSecurity;
use App\Filament\Resources\Areas\AreaResource;
use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Filament\Resources\Areas\Pages\EditArea;
use App\Models\Area;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_creating_a_record_redirects_back_to_the_list(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateArea::class)
            ->fillForm(['name' => 'New Area'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(AreaResource::getUrl('index'));
    }

    public function test_editing_a_record_redirects_back_to_the_list(): void
    {
        $this->actingAsSuperAdmin();
        $area = Area::factory()->create();

        Livewire::test(EditArea::class, ['record' => $area->getRouteKey()])
            ->fillForm(['name' => 'Renamed Area'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(AreaResource::getUrl('index'));
    }

    public function test_the_admin_panel_user_menu_links_profile_and_security_to_filament_pages(): void
    {
        $panel = Filament::getPanel('admin');
        $items = $panel->getUserMenuItems();

        $this->assertTrue($panel->hasProfile());
        $this->assertArrayHasKey('profile', $items);
        $this->assertSame($panel->getProfileUrl(), $items['profile']->getUrl());

        $this->assertArrayHasKey('security', $items);
        $this->assertSame(AdminSecurity::getUrl(), $items['security']->getUrl());
    }

    public function test_the_app_panel_user_menu_links_profile_and_security_to_filament_pages(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $panel = Filament::getPanel('app');
        $items = $panel->getUserMenuItems();

        $this->assertTrue($panel->hasProfile());
        $this->assertArrayHasKey('profile', $items);
        $this->assertSame($panel->getProfileUrl(), $items['profile']->getUrl());

        $this->assertArrayHasKey('security', $items);
        $this->assertSame(AppSecurity::getUrl(), $items['security']->getUrl());
    }

    public function test_the_admin_native_profile_page_renders(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(Filament::getPanel('admin')->getProfileUrl());

        $response->assertOk();
    }

    public function test_the_app_native_profile_page_renders(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));
        $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');

        $response = $this->get(Filament::getPanel('app')->getProfileUrl());

        $response->assertOk();
    }
}
