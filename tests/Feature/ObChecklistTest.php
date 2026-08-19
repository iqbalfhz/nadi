<?php

namespace Tests\Feature;

use App\Filament\App\Resources\ObChecklists\Pages\CreateObChecklist;
use App\Filament\App\Resources\ObChecklists\Pages\ListObChecklists;
use App\Models\ObArea;
use App\Models\ObChecklist;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ObChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
        Storage::fake('public');
    }

    public function test_an_employee_can_submit_a_checklist_with_photos(): void
    {
        $user = $this->actingAsEmployeeWithPermissions(['ViewAny:ObChecklist', 'Create:ObChecklist']);

        $area = ObArea::factory()->create(['is_active' => true]);

        Livewire::test(CreateObChecklist::class)
            ->fillForm([
                'ob_area_id' => $area->id,
                'photos' => [UploadedFile::fake()->image('lobby.jpg')],
                'notes' => 'Sudah bersih.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $checklist = ObChecklist::query()->where('ob_area_id', $area->id)->firstOrFail();

        $this->assertSame($user->id, $checklist->user_id);
        $this->assertSame('Sudah bersih.', $checklist->notes);
        $this->assertCount(1, $checklist->getMedia('photos'));
    }

    public function test_submitting_without_a_photo_fails_validation(): void
    {
        $this->actingAsEmployeeWithPermissions(['ViewAny:ObChecklist', 'Create:ObChecklist']);

        $area = ObArea::factory()->create(['is_active' => true]);

        Livewire::test(CreateObChecklist::class)
            ->fillForm([
                'ob_area_id' => $area->id,
                'photos' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['photos']);
    }

    public function test_the_list_only_shows_the_current_users_own_checklists(): void
    {
        $someoneElse = User::factory()->create();
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:ObChecklist');

        $myChecklist = ObChecklist::factory()->create(['user_id' => $me->id]);
        $otherChecklist = ObChecklist::factory()->create(['user_id' => $someoneElse->id]);

        Livewire::test(ListObChecklists::class)
            ->assertCanSeeTableRecords([$myChecklist])
            ->assertCanNotSeeTableRecords([$otherChecklist]);
    }
}
