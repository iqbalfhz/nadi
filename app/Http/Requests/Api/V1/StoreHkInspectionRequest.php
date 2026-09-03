<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Models\HkArea;
use App\Models\HkInspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mirrors HkInspectionForm.
 *
 * The two conditional fields are the interesting part. On the web they appear
 * and disappear as the supervisor fills the form; over an API there is no such
 * thing, so the rules have to state the same conditions outright.
 */
class StoreHkInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HkInspection::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // No hk_category_id: it is derived from the point, never sent.
            // See HkInspectionController::store().
            'hk_area_id' => [
                'required',
                Rule::exists(HkArea::class, 'id')->where('is_active', true),
            ],

            'staff_name' => ['required', 'string', 'max:255'],
            'shift' => ['required', Rule::enum(HkShift::class)],
            'condition' => ['required', Rule::enum(HkCondition::class)],

            // Only where the category asks for one.
            'floor' => [
                Rule::requiredIf(fn (): bool => $this->categoryRequiresFloor()),
                'nullable',
                'string',
                'max:255',
            ],

            // The rule that stops a supervisor reporting "Kotor" and walking
            // away without saying what was done about it. Keyed off the
            // finding, not the category — a clean Public Area needs no
            // follow-up either.
            'follow_up' => [
                Rule::requiredIf(fn (): bool => $this->conditionNeedsFollowUp()),
                'nullable',
                'string',
                'max:1000',
            ],

            'notes' => ['nullable', 'string', 'max:1000'],

            'photo_ids' => ['required', 'array', 'min:1', 'max:10'],
            'photo_ids.*' => [
                'uuid',
                Rule::exists('api_uploads', 'id')->where('user_id', $this->user()?->id),
            ],

            'submitted_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hk_area_id.required' => __('Titik wajib dipilih.'),
            'hk_area_id.exists' => __('Titik yang dipilih sudah tidak aktif.'),
            'staff_name.required' => __('Nama petugas wajib diisi.'),
            'shift.required' => __('Shift wajib dipilih.'),
            'condition.required' => __('Kondisi wajib dipilih.'),
            'floor.required' => __('Lantai wajib diisi untuk kategori ini.'),
            'follow_up.required' => __('Tindak lanjut wajib diisi kalau kondisinya bukan Bersih.'),
            'photo_ids.required' => __('Laporan wajib menyertakan foto.'),
            'photo_ids.min' => __('Laporan wajib menyertakan foto.'),
            'photo_ids.max' => __('Maksimal 10 foto per laporan.'),
            'photo_ids.*.exists' => __('Ada foto yang tidak ditemukan. Unggah ulang foto tersebut.'),
        ];
    }

    private function categoryRequiresFloor(): bool
    {
        // Cast before the lookup: find() hands back a Collection when given an
        // array of keys, and the payload is only ever a single point.
        $area = HkArea::query()->with('category')->find((int) $this->input('hk_area_id'));

        return $area?->category->requires_floor ?? false;
    }

    private function conditionNeedsFollowUp(): bool
    {
        $condition = HkCondition::tryFrom((string) $this->input('condition'));

        // HkCondition::needsFollowUp() is the single source of truth for this
        // rule; the web form reads the same method.
        return $condition?->needsFollowUp() ?? false;
    }
}
