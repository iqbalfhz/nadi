<?php

namespace Tests\Feature;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Filament\App\Resources\HkInspections\Pages\CreateHkInspection;
use App\Filament\App\Resources\HkInspections\Pages\ListHkInspections;
use App\Jobs\SendHkInspectionToTelegram;
use App\Models\HkArea;
use App\Models\HkCategory;
use App\Models\HkInspection;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class HkInspectionSubmitTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private const SUPERVISOR_PERMISSIONS = ['ViewAny:HkInspection', 'Create:HkInspection'];

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
        Queue::fake();
    }

    public function test_a_supervisor_can_file_an_inspection(): void
    {
        $supervisor = $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $category = HkCategory::factory()->create(['name' => 'Toilet']);
        $point = HkArea::factory()->for($category, 'category')->create(['name' => 'Lt 2 Zona A']);

        Livewire::test(CreateHkInspection::class)
            ->fillForm([
                'hk_category_id' => $category->id,
                'hk_area_id' => $point->id,
                'staff_name' => 'Budi',
                'shift' => HkShift::Pagi->value,
                'condition' => HkCondition::Bersih->value,
                'photos' => [UploadedFile::fake()->image('toilet.jpg')],
                'notes' => 'Semua rapi.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $inspection = HkInspection::query()->firstOrFail();

        $this->assertSame($supervisor->id, $inspection->user_id);
        $this->assertSame('Budi', $inspection->staff_name);
        $this->assertSame(HkCondition::Bersih, $inspection->condition);
        $this->assertSame(HkShift::Pagi, $inspection->shift);
        $this->assertCount(1, $inspection->getMedia('photos'));
    }

    /**
     * The point dropdown is filtered by the chosen category, and that filter
     * is also what validates the submission — so a payload pairing a point
     * with the wrong category is refused before it can be saved.
     */
    public function test_a_point_from_another_category_is_refused(): void
    {
        $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $toilet = HkCategory::factory()->create(['name' => 'Toilet']);
        $publicArea = HkCategory::factory()->create(['name' => 'Public Area']);
        $toiletPoint = HkArea::factory()->for($toilet, 'category')->create();

        Livewire::test(CreateHkInspection::class)
            ->fillForm([
                // Deliberately mismatched with the point's real category.
                'hk_category_id' => $publicArea->id,
                'hk_area_id' => $toiletPoint->id,
                'staff_name' => 'Budi',
                'shift' => HkShift::Pagi->value,
                'condition' => HkCondition::Bersih->value,
                'photos' => [UploadedFile::fake()->image('toilet.jpg')],
            ])
            ->call('create')
            ->assertHasFormErrors(['hk_area_id']);

        $this->assertSame(0, HkInspection::query()->count());
    }

    public function test_a_deactivated_point_is_refused(): void
    {
        $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $point = HkArea::factory()->create(['is_active' => false]);

        Livewire::test(CreateHkInspection::class)
            ->fillForm([
                'hk_category_id' => $point->hk_category_id,
                'hk_area_id' => $point->id,
                'staff_name' => 'Budi',
                'shift' => HkShift::Pagi->value,
                'condition' => HkCondition::Bersih->value,
                'photos' => [UploadedFile::fake()->image('toilet.jpg')],
            ])
            ->call('create')
            ->assertHasFormErrors(['hk_area_id']);
    }

    /**
     * Belt-and-braces behind the validation above: the stored category is read
     * back off the chosen point rather than trusted from the payload, so even
     * a caller that bypassed the dropdown cannot file a report under a
     * category the point does not belong to.
     */
    public function test_the_stored_category_is_derived_from_the_point(): void
    {
        $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $toilet = HkCategory::factory()->create(['name' => 'Toilet']);
        $publicArea = HkCategory::factory()->create(['name' => 'Public Area']);
        $toiletPoint = HkArea::factory()->for($toilet, 'category')->create();

        $page = Livewire::test(CreateHkInspection::class)->instance();

        $mutate = new ReflectionMethod($page, 'mutateFormDataBeforeCreate');

        /** @var array<string, mixed> $data */
        $data = $mutate->invoke($page, [
            'hk_category_id' => $publicArea->id,
            'hk_area_id' => $toiletPoint->id,
            'condition' => HkCondition::Bersih->value,
            'floor' => 'Lantai 5',
            'follow_up' => 'Tidak relevan.',
        ]);

        $this->assertSame($toilet->id, $data['hk_category_id']);
        // Toilet does not ask for a floor, and a clean report carries no
        // follow-up — both are dropped rather than stored as stray values.
        $this->assertNull($data['floor']);
        $this->assertNull($data['follow_up']);
    }

    public function test_a_finding_cannot_be_filed_without_a_follow_up(): void
    {
        $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $point = HkArea::factory()->create();

        Livewire::test(CreateHkInspection::class)
            ->fillForm([
                'hk_category_id' => $point->hk_category_id,
                'hk_area_id' => $point->id,
                'staff_name' => 'Budi',
                'shift' => HkShift::Malam->value,
                'condition' => HkCondition::Kotor->value,
                'photos' => [UploadedFile::fake()->image('toilet.jpg')],
                'follow_up' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['follow_up']);

        $this->assertSame(0, HkInspection::query()->count());
    }

    public function test_a_clean_report_stores_no_follow_up(): void
    {
        $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $point = HkArea::factory()->create();

        Livewire::test(CreateHkInspection::class)
            ->fillForm([
                'hk_category_id' => $point->hk_category_id,
                'hk_area_id' => $point->id,
                'staff_name' => 'Budi',
                'shift' => HkShift::Siang->value,
                'condition' => HkCondition::Bersih->value,
                'photos' => [UploadedFile::fake()->image('toilet.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(HkInspection::query()->firstOrFail()->follow_up);
    }

    /**
     * A floor is only meaningful where the category asks for one — for Toilet
     * it is already inside the point's name ("Lt 2 Zona A").
     */
    public function test_the_floor_is_only_kept_for_categories_that_ask_for_it(): void
    {
        $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $toilet = HkCategory::factory()->create(['requires_floor' => false]);
        $point = HkArea::factory()->for($toilet, 'category')->create();

        Livewire::test(CreateHkInspection::class)
            ->fillForm([
                'hk_category_id' => $toilet->id,
                'hk_area_id' => $point->id,
                'staff_name' => 'Budi',
                'shift' => HkShift::Pagi->value,
                'condition' => HkCondition::Bersih->value,
                'floor' => 'Lantai 5',
                'photos' => [UploadedFile::fake()->image('toilet.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(HkInspection::query()->firstOrFail()->floor);
    }

    public function test_a_category_that_requires_a_floor_will_not_accept_a_blank_one(): void
    {
        $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $publicArea = HkCategory::factory()->requiringFloor()->create();
        $point = HkArea::factory()->for($publicArea, 'category')->create();

        Livewire::test(CreateHkInspection::class)
            ->fillForm([
                'hk_category_id' => $publicArea->id,
                'hk_area_id' => $point->id,
                'staff_name' => 'Budi',
                'shift' => HkShift::Pagi->value,
                'condition' => HkCondition::Bersih->value,
                'floor' => '',
                'photos' => [UploadedFile::fake()->image('lobby.jpg')],
            ])
            ->call('create')
            ->assertHasFormErrors(['floor']);
    }

    public function test_filing_a_report_queues_the_telegram_delivery(): void
    {
        $this->actingAsEmployeeWithPermissions(self::SUPERVISOR_PERMISSIONS);

        $point = HkArea::factory()->create();

        Livewire::test(CreateHkInspection::class)
            ->fillForm([
                'hk_category_id' => $point->hk_category_id,
                'hk_area_id' => $point->id,
                'staff_name' => 'Budi',
                'shift' => HkShift::Pagi->value,
                'condition' => HkCondition::Bersih->value,
                'photos' => [UploadedFile::fake()->image('toilet.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $inspection = HkInspection::query()->firstOrFail();

        Queue::assertPushed(
            SendHkInspectionToTelegram::class,
            fn (SendHkInspectionToTelegram $job): bool => $job->inspectionId === $inspection->id,
        );
    }

    public function test_the_list_only_shows_the_supervisors_own_reports(): void
    {
        $someoneElse = User::factory()->create();
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:HkInspection');

        $mine = HkInspection::factory()->create(['user_id' => $me->id]);
        $theirs = HkInspection::factory()->create(['user_id' => $someoneElse->id]);

        Livewire::test(ListHkInspections::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_an_employee_without_the_permission_cannot_file_a_report(): void
    {
        $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');

        Livewire::test(CreateHkInspection::class)->assertForbidden();
    }
}
