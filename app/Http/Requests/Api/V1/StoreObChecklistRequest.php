<?php

namespace App\Http\Requests\Api\V1;

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

            'photo_ids' => ['required', 'array', 'min:1', 'max:10'],
            'photo_ids.*' => [
                'uuid',
                // Scoped to the uploader, so another worker's upload id reads
                // as simply not existing — which is the right answer, and
                // gives away nothing about whether it does.
                Rule::exists('api_uploads', 'id')->where('user_id', $this->user()?->id),
            ],

            // When the worker says they did it, for reports queued offline.
            // Out-of-range values are clamped rather than rejected — see
            // ObChecklistController::submittedAt().
            'submitted_at' => ['nullable', 'date'],
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
            'photo_ids.required' => __('Laporan wajib menyertakan foto.'),
            'photo_ids.min' => __('Laporan wajib menyertakan foto.'),
            'photo_ids.max' => __('Maksimal 10 foto per laporan.'),
            'photo_ids.*.exists' => __('Ada foto yang tidak ditemukan. Unggah ulang foto tersebut.'),
        ];
    }
}
