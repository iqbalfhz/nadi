<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type_id' => DocumentType::factory(),
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'number' => fake()->unique()->numberBetween(1, 999),
            'subject' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
