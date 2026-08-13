<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    /**
     * The number isn't something the form collects — it's computed by
     * Document::createNextFor(), which also handles the locking that keeps
     * it gap-free and collision-safe under concurrent submissions.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $creator */
        $creator = Auth::user();

        return Document::createNextFor(
            documentType: DocumentType::query()->findOrFail((int) $data['document_type_id']),
            company: Company::query()->findOrFail((int) $data['company_id']),
            department: Department::query()->findOrFail((int) $data['department_id']),
            subject: (string) $data['subject'],
            creator: $creator,
        );
    }
}
