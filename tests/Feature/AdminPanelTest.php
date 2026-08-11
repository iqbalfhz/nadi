<?php

namespace Tests\Feature;

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
}
