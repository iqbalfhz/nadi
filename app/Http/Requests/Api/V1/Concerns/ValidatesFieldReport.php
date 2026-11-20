<?php

namespace App\Http\Requests\Api\V1\Concerns;

use Illuminate\Validation\Rule;

/**
 * What every offline field report carries besides its own fields: the photos
 * staged through POST /uploads and the time the worker says they filed it.
 */
trait ValidatesFieldReport
{
    /**
     * @return array<string, mixed>
     */
    protected function fieldReportRules(): array
    {
        return [
            'photo_ids' => ['required', 'array', 'min:1', 'max:10'],
            'photo_ids.*' => [
                'uuid',
                // Scoped to the uploader, so another worker's upload id reads
                // as simply not existing — which is the right answer, and
                // gives away nothing about whether it does.
                Rule::exists('api_uploads', 'id')->where('user_id', $this->user()?->id),
            ],

            // Out-of-range values are clamped rather than rejected — see FieldReportTime::clamp().
            'submitted_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldReportMessages(): array
    {
        return [
            'photo_ids.required' => __('Laporan wajib menyertakan foto.'),
            'photo_ids.min' => __('Laporan wajib menyertakan foto.'),
            'photo_ids.max' => __('Maksimal 10 foto per laporan.'),
            'photo_ids.*.exists' => __('Ada foto yang tidak ditemukan. Unggah ulang foto tersebut.'),
        ];
    }
}
