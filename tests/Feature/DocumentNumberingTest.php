<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_the_next_document_assigns_sequential_numbers_per_type_and_company(): void
    {
        $type = DocumentType::factory()->create(['code' => 'K']);
        $company = Company::factory()->create(['code' => 'E']);
        $department = Department::factory()->create();
        $creator = User::factory()->create();

        $first = Document::createNextFor($type, $company, $department, 'Perihal pertama', $creator);
        $second = Document::createNextFor($type, $company, $department, 'Perihal kedua', $creator);

        $this->assertSame(1, $first->number);
        $this->assertSame(2, $second->number);
    }

    public function test_numbering_is_independent_per_company(): void
    {
        // Real-world evidence for this: KE/TERE/07/26/076 (PT EFM) and
        // KS/TERE/07/26/003 (PT SSK) issued the same month, wildly different
        // ranges — each PT runs its own counter for the same document type.
        $type = DocumentType::factory()->create(['code' => 'K']);
        $companyA = Company::factory()->create(['code' => 'E']);
        $companyB = Company::factory()->create(['code' => 'S']);
        $department = Department::factory()->create();
        $creator = User::factory()->create();

        Document::createNextFor($type, $companyA, $department, 'A', $creator);
        Document::createNextFor($type, $companyA, $department, 'A2', $creator);
        $firstB = Document::createNextFor($type, $companyB, $department, 'B', $creator);

        $this->assertSame(1, $firstB->number);
    }

    public function test_numbering_is_shared_across_departments_for_the_same_type_and_company(): void
    {
        $type = DocumentType::factory()->create();
        $company = Company::factory()->create();
        $deptA = Department::factory()->create();
        $deptB = Department::factory()->create();
        $creator = User::factory()->create();

        Document::createNextFor($type, $company, $deptA, 'A', $creator);
        $second = Document::createNextFor($type, $company, $deptB, 'B', $creator);

        $this->assertSame(2, $second->number);
    }

    public function test_numbering_resets_for_a_new_month(): void
    {
        $type = DocumentType::factory()->create();
        $company = Company::factory()->create();
        $department = Department::factory()->create();
        $creator = User::factory()->create();

        Document::factory()->create([
            'document_type_id' => $type->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'number' => 99,
            'created_at' => now()->subMonthNoOverflow(),
        ]);

        $document = Document::createNextFor($type, $company, $department, 'Baru', $creator);

        $this->assertSame(1, $document->number);
    }

    public function test_formatted_number_matches_the_expected_pattern(): void
    {
        $type = DocumentType::factory()->create(['code' => 'K']);
        $company = Company::factory()->create(['code' => 'E']);
        $department = Department::factory()->create(['code' => 'TERE']);
        $creator = User::factory()->create();

        $document = Document::createNextFor($type, $company, $department, 'Perihal', $creator);

        $month = now()->format('m');
        $year = now()->format('y');

        $this->assertSame("KE/TERE/{$month}/{$year}/001", $document->formatted_number);
    }
}
