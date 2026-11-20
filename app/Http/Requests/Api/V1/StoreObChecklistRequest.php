<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesFieldReport;
use App\Models\ObArea;
use App\Models\ObChecklist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mirrors ObChecklistForm, which is where these rules live for the web.
 *
 * They have to be restated rather than shared: a Filament schema is a
 * description of a form, not a set of validation rules, and there is no seam
 * to reuse. Any change to one belongs in the other on the same day —
 * ObChecklistApiTest asserts the limits that matter.
 */
class StoreObChecklistRequest extends FormRequest
{
    use ValidatesFieldReport;

    /**
     * Reaching the mobile app is not the same as being an OB.
     *
     * EnsureMobileAccess only asks whether this account belongs on a phone at
     * all — a security guard passes it. This is what asks whether they may
     * file *this* kind of report, and it runs before validation so an
     * unauthorised caller never learns which fields exist.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ObChecklist::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ob_area_id' => [
                'required',
                // Only active areas, matching the form's options query: a
                // retired area must not come back through the phone.
                Rule::exists(ObArea::class, 'id')->where('is_active', true),
            ],

            'notes' => ['nullable', 'string', 'max:1000'],

            ...$this->fieldReportRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ob_area_id.required' => __('Area wajib dipilih.'),
            'ob_area_id.exists' => __('Area yang dipilih sudah tidak aktif.'),
            ...$this->fieldReportMessages(),
        ];
    }
}
