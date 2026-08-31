<?php

namespace Tests\Feature;

use App\Enums\HkCondition;
use App\Filament\Resources\HkAreas\Pages\ListHkAreas;
use App\Filament\Resources\HkCategories\Pages\CreateHkCategory;
use App\Filament\Resources\HkCategories\Pages\ListHkCategories;
use App\Filament\Resources\HkInspections\HkInspectionResource;
use App\Filament\Resources\HkInspections\Pages\ListHkInspections;
use App\Models\HkArea;
use App\Models\HkCategory;
use App\Models\HkInspection;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HkAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
    }

    public function test_an_admin_can_create_a_category(): void
    {
        Livewire::test(CreateHkCategory::class)
            ->fillForm([
                'name' => 'Public Area',
                'requires_floor' => true,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = HkCategory::query()->firstOrFail();

        $this->assertSame('Public Area', $category->name);
        $this->assertTrue($category->requires_floor);
    }

    /**
     * restrictOnDelete() would otherwise reject this at the database level and
     * surface as a raw QueryException. History has to stay intact, so the
     * admin is told to deactivate instead.
     */
    public function test_a_point_with_reports_cannot_be_deleted(): void
    {
        $inspection = HkInspection::factory()->create();
        $point = $inspection->area;

        Livewire::test(ListHkAreas::class)
            ->callAction(TestAction::make('delete')->table($point));

        $this->assertModelExists($point);
    }

    public function test_a_category_that_still_has_points_cannot_be_deleted(): void
    {
        $category = HkCategory::factory()->create();
        HkArea::factory()->for($category, 'category')->create();

        Livewire::test(ListHkCategories::class)
            ->callAction(TestAction::make('delete')->table($category));

        $this->assertModelExists($category);
    }

    public function test_an_empty_category_can_still_be_deleted(): void
    {
        $category = HkCategory::factory()->create();

        Livewire::test(ListHkCategories::class)
            ->callAction(TestAction::make('delete')->table($category));

        $this->assertModelMissing($category);
    }

    /**
     * A filed inspection records what a supervisor found at a moment in time,
     * so there is deliberately no way to alter it after the fact.
     */
    public function test_reports_are_read_only(): void
    {
        $this->assertSame(['index'], array_keys(HkInspectionResource::getPages()));
    }

    public function test_reports_can_be_filtered_by_category_and_condition(): void
    {
        $toilet = HkCategory::factory()->create(['name' => 'Toilet']);
        $publicArea = HkCategory::factory()->create(['name' => 'Public Area']);

        $dirtyToilet = HkInspection::factory()
            ->withFinding(HkCondition::Kotor)
            ->for(HkArea::factory()->for($toilet, 'category'), 'area')
            ->create(['hk_category_id' => $toilet->id]);

        $cleanToilet = HkInspection::factory()
            ->for(HkArea::factory()->for($toilet, 'category'), 'area')
            ->create(['hk_category_id' => $toilet->id]);

        $lobby = HkInspection::factory()
            ->for(HkArea::factory()->for($publicArea, 'category'), 'area')
            ->create(['hk_category_id' => $publicArea->id]);

        Livewire::test(ListHkInspections::class)
            ->filterTable('hk_category_id', $toilet->id)
            ->assertCanSeeTableRecords([$dirtyToilet, $cleanToilet])
            ->assertCanNotSeeTableRecords([$lobby])
            ->filterTable('condition', HkCondition::Kotor->value)
            ->assertCanSeeTableRecords([$dirtyToilet])
            ->assertCanNotSeeTableRecords([$cleanToilet]);
    }

    /**
     * The /admin report is company-wide, unlike the supervisor's own list in
     * /app — that difference is the whole point of having both.
     */
    public function test_the_admin_report_shows_every_supervisors_reports(): void
    {
        $first = HkInspection::factory()->create();
        $second = HkInspection::factory()->create();

        $this->assertNotSame($first->user_id, $second->user_id);

        Livewire::test(ListHkInspections::class)
            ->assertCanSeeTableRecords([$first, $second]);
    }
}
