<?php

namespace Tests\Feature;

use App\Enums\HkCondition;
use App\Models\HkArea;
use App\Models\HkCategory;
use App\Models\HkInspection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HkInspectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The rule that decides whether the "Tindak Lanjut" field appears and is
     * required. Asserted on the enum rather than through the form so the rule
     * has one owner.
     */
    #[DataProvider('conditions')]
    public function test_only_a_finding_needs_a_follow_up(HkCondition $condition, bool $expected): void
    {
        $this->assertSame($expected, $condition->needsFollowUp());
    }

    /**
     * @return array<string, array{HkCondition, bool}>
     */
    public static function conditions(): array
    {
        return [
            'bersih' => [HkCondition::Bersih, false],
            'perlu perbaikan' => [HkCondition::PerluPerbaikan, true],
            'kotor' => [HkCondition::Kotor, true],
        ];
    }

    public function test_every_condition_and_shift_has_indonesian_labels(): void
    {
        foreach (HkCondition::cases() as $condition) {
            $this->assertNotSame('', $condition->label());
            $this->assertNotSame('', $condition->emoji());
            $this->assertContains($condition->color(), ['success', 'warning', 'danger']);
        }

        $this->assertSame(
            ['bersih' => 'Bersih', 'perlu_perbaikan' => 'Perlu Perbaikan', 'kotor' => 'Kotor'],
            HkCondition::options(),
        );
    }

    public function test_an_area_belongs_to_exactly_one_category(): void
    {
        $toilet = HkCategory::factory()->create(['name' => 'Toilet']);
        $publicArea = HkCategory::factory()->requiringFloor()->create(['name' => 'Public Area']);

        $point = HkArea::factory()->for($toilet, 'category')->create(['name' => 'Lt 2 Zona A']);

        $this->assertTrue($point->category->is($toilet));
        $this->assertFalse($point->category->is($publicArea));
        $this->assertTrue($toilet->areas->contains($point));
    }

    /**
     * Evidence photos must never land on the public disk, which publishes them
     * at a guessable /storage/{id}/ URL with no login at all.
     */
    public function test_photos_are_stored_on_the_private_disk(): void
    {
        $inspection = HkInspection::factory()->create();

        $inspection->addMedia(UploadedFile::fake()->image('toilet.jpg'))
            ->toMediaCollection('photos');

        $media = $inspection->getFirstMedia('photos');

        $this->assertNotNull($media);
        $this->assertSame('internal', $media->disk);

        $inspection->clearMediaCollection('photos');
    }

    public function test_a_category_that_wants_a_floor_says_so(): void
    {
        $this->assertFalse(HkCategory::factory()->create()->requires_floor);
        $this->assertTrue(HkCategory::factory()->requiringFloor()->create()->requires_floor);
    }
}
