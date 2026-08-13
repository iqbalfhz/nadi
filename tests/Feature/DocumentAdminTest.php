<?php

namespace Tests\Feature;

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use App\Filament\Resources\DocumentTypes\Pages\CreateDocumentType;
use App\Models\Company;
use App\Models\Department;
use App\Models\DocumentType;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_creating_a_document_type_redirects_back_to_the_list(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateDocumentType::class)
            ->fillForm([
                'name' => 'Surat Keluar',
                'code' => 'k',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(DocumentTypeResource::getUrl('index'));

        $this->assertDatabaseHas('document_types', [
            'name' => 'Surat Keluar',
            'code' => 'K',
        ]);
    }

    public function test_creating_a_document_issues_the_next_number_and_redirects_to_the_list(): void
    {
        $this->actingAsSuperAdmin();
        $type = DocumentType::factory()->create(['code' => 'K']);
        $company = Company::factory()->create(['code' => 'E']);
        $department = Department::factory()->create(['code' => 'TERE']);

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'document_type_id' => $type->id,
                'company_id' => $company->id,
                'department_id' => $department->id,
                'subject' => 'Undangan rapat',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(DocumentResource::getUrl('index'));

        $this->assertDatabaseHas('documents', [
            'document_type_id' => $type->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'number' => 1,
            'subject' => 'Undangan rapat',
        ]);
    }

    public function test_the_document_resource_has_no_edit_capability(): void
    {
        $this->assertArrayNotHasKey('edit', DocumentResource::getPages());
    }
}
