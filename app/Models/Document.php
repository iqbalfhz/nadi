<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['document_type_id', 'company_id', 'department_id', 'number', 'subject', 'created_by'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Dokumen';
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedNumber(): Attribute
    {
        return Attribute::make(
            get: fn (): string => sprintf(
                '%s%s/%s/%s/%s/%03d',
                $this->documentType->code,
                $this->company->code,
                $this->department->code,
                $this->created_at->format('m'),
                $this->created_at->format('y'),
                $this->number,
            ),
        );
    }

    /**
     * Issue the next number for a document type + company, scoped to the
     * current calendar month — locks the document type row so two people
     * creating a document for the same type+company at once can't collide.
     * Department doesn't affect the sequence, only what's displayed.
     */
    public static function createNextFor(
        DocumentType $documentType,
        Company $company,
        Department $department,
        string $subject,
        User $creator,
    ): self {
        return DB::transaction(function () use ($documentType, $company, $department, $subject, $creator): self {
            DocumentType::query()->whereKey($documentType->id)->lockForUpdate()->first();

            $lastNumber = static::query()
                ->where('document_type_id', $documentType->id)
                ->where('company_id', $company->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->max('number');

            return static::create([
                'document_type_id' => $documentType->id,
                'company_id' => $company->id,
                'department_id' => $department->id,
                'number' => ((int) $lastNumber) + 1,
                'subject' => $subject,
                'created_by' => $creator->id,
            ]);
        });
    }
}
