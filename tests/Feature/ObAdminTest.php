<?php

namespace Tests\Feature;

use App\Filament\Resources\ObAreas\ObAreaResource;
use App\Filament\Resources\ObAreas\Pages\CreateObArea;
use App\Filament\Resources\ObChecklists\Pages\ListObChecklists;
use App\Models\ObChecklist;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ObAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_an_admin_can_create_an_ob_area(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateObArea::class)
            ->fillForm([
                'name' => 'Toilet Lt 2 Zona A',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(ObAreaResource::getUrl('index'));

        $this->assertDatabaseHas('ob_areas', [
            'name' => 'Toilet Lt 2 Zona A',
            'is_active' => true,
        ]);
    }

    public function test_the_admin_checklist_report_shows_submissions_from_every_user(): void
    {
        $this->actingAsSuperAdmin();

        $checklistA = ObChecklist::factory()->create();
        $checklistB = ObChecklist::factory()->create(['user_id' => User::factory()->create()->id]);

        Livewire::test(ListObChecklists::class)
            ->assertCanSeeTableRecords([$checklistA, $checklistB]);
    }
}
