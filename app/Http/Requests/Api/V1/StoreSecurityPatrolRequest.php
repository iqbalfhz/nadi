<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SecurityCheckpoint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mirrors the SecurityScan page, which is where these rules live for the web.
 */
class StoreSecurityPatrolRequest extends FormRequest
{
    /**
     * Gated on the page permission, not on Create:SecurityPatrol.
     *
     * That is deliberate and matches the web: SecurityScan uses HasPageShield,
     * so 'View:SecurityScan' is what actually decides who may file a patrol.
     * Guards do not hold Create:SecurityPatrol at all — checking it here would
     * lock out every one of them.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('View:SecurityScan') ?? false;
    }

    /**
     * Accept whatever the scanner read, not only the bare code.
     *
     * A patrol filed offline carries the raw scan in its outbox, and the
     * sticker holds a URL. Without this, every queued patrol would fail
     * validation hours later — long after the guard left the post and could
     * do anything about it.
     */
    protected function prepareForValidation(): void
    {
        $scanned = $this->input('checkpoint_code');

        if (is_string($scanned)) {
            $this->merge(['checkpoint_code' => SecurityCheckpoint::codeFromScan($scanned)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The code from the QR sticker, not a checkpoint id. Reaching the
            // sticker is the evidence that the guard was there, so the id is
            // never accepted from the client.
            'checkpoint_code' => [
                'required',
                'string',
                Rule::exists(SecurityCheckpoint::class, 'code')->where('is_active', true),
            ],

            'incident_report' => ['nullable', 'string', 'max:1000'],

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
            'checkpoint_code.required' => __('Kode titik patroli tidak terbaca.'),
            'checkpoint_code.exists' => __('QR ini tidak dikenali, atau titik patroli sudah dinonaktifkan.'),
            'photo_ids.required' => __('Laporan wajib menyertakan foto.'),
            'photo_ids.min' => __('Laporan wajib menyertakan foto.'),
            'photo_ids.max' => __('Maksimal 10 foto per laporan.'),
            'photo_ids.*.exists' => __('Ada foto yang tidak ditemukan. Unggah ulang foto tersebut.'),
        ];
    }
}
