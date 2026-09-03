<?php

namespace Tests\Feature\Api;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Jobs\SendHkInspectionToTelegram;
use App\Models\HkArea;
use App\Models\HkCategory;
use App\Models\HkInspection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Inspeksi HK over the API.
 *
 * The conditional fields are the point. On the web they appear and vanish as
 * the supervisor types; over an API nothing appears or vanishes, so the rules
 * have to hold on their own.
 */
class HkInspectionApiTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ['ViewAny:HkInspection', 'Create:HkInspection'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
        Queue::fake();
    }

    public function test_a_supervisor_can_file_an_inspection(): void
    {
        $supervisor = $this->actingAsMobileUser(self::PERMISSIONS);
        $area = $this->area();

        $response = $this->postJson('/api/v1/hk/inspections', [
            'hk_area_id' => $area->id,
            'staff_name' => 'Siti',
            'shift' => HkShift::Pagi->value,
            'condition' => HkCondition::Bersih->value,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader());

        $response->assertCreated();

        $inspection = HkInspection::query()->sole();

        $this->assertSame($area->id, $inspection->hk_area_id);
        $this->assertSame($supervisor->id, $inspection->user_id);
        $this->assertSame('Siti', $inspection->staff_name);
        $this->assertSame(HkCondition::Bersih, $inspection->condition);
    }

    /**
     * Every report in /admin is filtered by category, so a mismatched pair
     * would file the report somewhere nobody looks.
     *
     * Two layers hold this, and it is worth knowing which does the work:
     * StoreHkInspectionRequest simply has no hk_category_id rule, so
     * validated() drops the field before the controller ever sees it — that
     * is the primary guard. The controller then derives the category from the
     * point anyway, as a second line for any future caller that bypasses the
     * form request.
     *
     * This test was checked by disabling each in turn: it only fails when
     * *both* are gone. So it proves the invariant, not either mechanism —
     * don't take it as cover for removing one of them.
     */
    public function test_the_category_is_derived_from_the_point(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $category = HkCategory::factory()->create(['is_active' => true]);
        $area = HkArea::factory()->create(['hk_category_id' => $category->id, 'is_active' => true]);

        $this->postJson('/api/v1/hk/inspections', [
            'hk_area_id' => $area->id,
            // Sent on purpose, and expected to be ignored.
            'hk_category_id' => HkCategory::factory()->create()->id,
            'staff_name' => 'Siti',
            'shift' => HkShift::Pagi->value,
            'condition' => HkCondition::Bersih->value,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertCreated();

        $this->assertSame($category->id, HkInspection::query()->sole()->hk_category_id);
    }

    /**
     * The rule that stops a supervisor reporting "Kotor" and walking away
     * without saying what was done about it.
     */
    public function test_a_finding_cannot_be_filed_without_a_follow_up(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/hk/inspections', [
            'hk_area_id' => $this->area()->id,
            'staff_name' => 'Siti',
            'shift' => HkShift::Pagi->value,
            'condition' => HkCondition::Kotor->value,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('follow_up');

        $this->assertSame(0, HkInspection::query()->count());
    }

    public function test_a_clean_report_drops_a_follow_up_it_was_sent(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/hk/inspections', [
            'hk_area_id' => $this->area()->id,
            'staff_name' => 'Siti',
            'shift' => HkShift::Pagi->value,
            'condition' => HkCondition::Bersih->value,
            'follow_up' => 'tidak relevan',
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertCreated();

        $this->assertNull(HkInspection::query()->sole()->follow_up);
    }

    public function test_a_category_that_asks_for_a_floor_requires_one(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $area = $this->area(requiresFloor: true);

        $this->postJson('/api/v1/hk/inspections', [
            'hk_area_id' => $area->id,
            'staff_name' => 'Siti',
            'shift' => HkShift::Pagi->value,
            'condition' => HkCondition::Bersih->value,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('floor');
    }

    public function test_a_category_that_does_not_ask_for_a_floor_drops_one(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/hk/inspections', [
            'hk_area_id' => $this->area(requiresFloor: false)->id,
            'staff_name' => 'Siti',
            'shift' => HkShift::Pagi->value,
            'condition' => HkCondition::Bersih->value,
            'floor' => 'Lantai 3',
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertCreated();

        $this->assertNull(HkInspection::query()->sole()->floor);
    }

    /**
     * The report is the thing that must survive; Telegram is a courtesy. The
     * job is queued only once the photos are attached, so it finds them.
     */
    public function test_filing_queues_the_telegram_delivery(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/hk/inspections', [
            'hk_area_id' => $this->area()->id,
            'staff_name' => 'Siti',
            'shift' => HkShift::Pagi->value,
            'condition' => HkCondition::Bersih->value,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertCreated();

        $inspection = HkInspection::query()->sole();

        Queue::assertPushed(
            SendHkInspectionToTelegram::class,
            fn (SendHkInspectionToTelegram $job): bool => $job->inspectionId === $inspection->id,
        );

        $this->assertSame(1, $inspection->getMedia('photos')->count());
    }

    /**
     * The whole point tree in one call, so the phone can render the
     * conditional form with no signal.
     */
    public function test_the_category_tree_carries_its_points_and_floor_flag(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $category = HkCategory::factory()->create(['name' => 'Toilet', 'requires_floor' => true, 'is_active' => true]);
        HkArea::factory()->create(['hk_category_id' => $category->id, 'name' => 'Lt 2 Zona A', 'is_active' => true]);
        HkArea::factory()->create(['hk_category_id' => $category->id, 'is_active' => false]);

        $response = $this->getJson('/api/v1/hk/categories');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Toilet')
            ->assertJsonPath('data.0.requires_floor', true)
            ->assertJsonPath('data.0.areas.0.name', 'Lt 2 Zona A');

        // The retired point is not offered.
        $this->assertCount(1, $response->json('data.0.areas'));
    }

    public function test_the_options_endpoint_says_which_conditions_need_a_follow_up(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $conditions = collect($this->getJson('/api/v1/hk/options')->json('data.conditions'))
            ->pluck('needs_follow_up', 'value');

        $this->assertFalse($conditions[HkCondition::Bersih->value]);
        $this->assertTrue($conditions[HkCondition::Kotor->value]);
        $this->assertTrue($conditions[HkCondition::PerluPerbaikan->value]);
    }

    public function test_the_list_only_shows_the_supervisors_own_reports(): void
    {
        $supervisor = $this->actingAsMobileUser(self::PERMISSIONS);

        $mine = HkInspection::factory()->create(['user_id' => $supervisor->id]);
        $theirs = HkInspection::factory()->create();

        $ids = collect($this->getJson('/api/v1/hk/inspections')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));
    }

    public function test_a_retired_point_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $retired = HkArea::factory()->create([
            'hk_category_id' => HkCategory::factory()->create()->id,
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/hk/inspections', [
            'hk_area_id' => $retired->id,
            'staff_name' => 'Siti',
            'shift' => HkShift::Pagi->value,
            'condition' => HkCondition::Bersih->value,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('hk_area_id');
    }

    public function test_an_employee_without_the_permission_is_refused(): void
    {
        $this->actingAsMobileUser('Create:ObChecklist');

        $this->getJson('/api/v1/hk/categories')->assertForbidden();
    }

    private function area(bool $requiresFloor = false): HkArea
    {
        return HkArea::factory()->create([
            'hk_category_id' => HkCategory::factory()->create([
                'requires_floor' => $requiresFloor,
                'is_active' => true,
            ])->id,
            'is_active' => true,
        ]);
    }

    private function upload(): string
    {
        return $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader())->json('data.id');
    }
}
