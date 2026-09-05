<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deliberately forgiving, and that is the whole design.
 *
 * Everything here except the message is optional, and the limits are caps
 * rather than requirements. A crash report is the app telling on itself while
 * something is already wrong; refusing one over a missing device name would
 * throw away the only evidence that the failure ever happened.
 *
 * The one thing that must be bounded is size — a runaway stack trace should
 * not be able to write megabytes per request.
 */
class StoreCrashReportRequest extends FormRequest
{
    /**
     * No permission check. Any signed-in account may report that their own
     * app failed, including one whose module access was just revoked — that
     * revocation is itself a plausible cause of the crash.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1000'],
            'stack' => ['nullable', 'string', 'max:20000'],
            'app_version' => ['nullable', 'string', 'max:32'],
            'platform' => ['nullable', 'string', 'max:16'],
            'device' => ['nullable', 'string', 'max:128'],
            'os_version' => ['nullable', 'string', 'max:32'],

            // When the app failed, not when it managed to tell us. Clamped
            // rather than rejected, exactly like a field report's
            // submitted_at — see App\Support\FieldReportTime.
            'occurred_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => __('Laporan kegagalan tanpa pesan tidak bisa disimpan.'),
        ];
    }

    /**
     * The validated payload, shaped for AppCrashReport::record().
     *
     * Built here rather than in the controller because this is the class that
     * knows what the rules guarantee — validated() is typed mixed, and the
     * narrowing belongs next to the rules that justify it.
     *
     * @return array{message: string, stack: string|null, app_version: string|null, platform: string|null, device: string|null, os_version: string|null}
     */
    public function crash(): array
    {
        return [
            // Guaranteed present and a string by rules(); the check is what
            // carries that guarantee out of validated().
            'message' => $this->optionalString('message') ?? '',
            'stack' => $this->optionalString('stack'),
            'app_version' => $this->optionalString('app_version'),
            'platform' => $this->optionalString('platform'),
            'device' => $this->optionalString('device'),
            'os_version' => $this->optionalString('os_version'),
        ];
    }

    /**
     * An empty string and an absent field mean the same thing here — a phone
     * that had nothing to put in the box — and storing "" would make the admin
     * table show a blank where it should show a dash.
     */
    private function optionalString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
