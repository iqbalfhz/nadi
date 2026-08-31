<?php

namespace Database\Factories;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Models\HkArea;
use App\Models\HkInspection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HkInspection>
 */
class HkInspectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hk_area_id' => HkArea::factory(),
            // Always the area's own category. The application derives this at
            // submit time rather than accepting it from the form, so letting a
            // factory produce a disagreeing pair would fabricate a state that
            // cannot occur in production.
            'hk_category_id' => fn (array $attributes): int => (int) HkArea::query()
                ->findOrFail((int) $attributes['hk_area_id'])
                ->hk_category_id,
            'user_id' => User::factory(),
            'staff_name' => fake()->name(),
            'shift' => fake()->randomElement(HkShift::cases()),
            'condition' => HkCondition::Bersih,
            'floor' => null,
            'notes' => fake()->optional()->sentence(),
            'follow_up' => null,
        ];
    }

    /**
     * A report with a finding, which is the only shape that carries a
     * follow-up action.
     */
    public function withFinding(HkCondition $condition = HkCondition::Kotor): static
    {
        return $this->state(fn (): array => [
            'condition' => $condition,
            'notes' => fake()->sentence(),
            'follow_up' => fake()->sentence(),
        ]);
    }
}
