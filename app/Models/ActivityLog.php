<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

/**
 * The activity log, as this app sees it.
 *
 * Subclasses Spatie's Activity purely so Filament has a model in the App
 * namespace to build a resource and a Shield policy against — the table and
 * behaviour are entirely Spatie's. Nothing writes through this class.
 */
class ActivityLog extends Activity
{
    /**
     * The log_name each source writes under, so the table can offer a filter
     * an admin recognises instead of raw strings. Ordered by how much
     * attention an entry deserves, which is also the colour order below.
     */
    public const LOG_NAMES = [
        'data' => 'Perubahan Data',
        'akses' => 'Akses & Login',
        'akses-data' => 'Lihat & Export Data',
        'sistem' => 'Sistem',
        'izin' => 'Role & Izin',
        'ditolak' => 'Akses Ditolak',
    ];

    /**
     * Colour by how much attention the entry deserves, not by giving each
     * type a different one for the sake of it — an admin scanning a long
     * list needs red to mean "look here", every time.
     *
     * grey    routine, the bulk of the log
     * blue    normal comings and goings
     * amber   somebody took a copy, or changed how the system runs
     * red     security: who can do what, and who was told no
     */
    public const LOG_COLORS = [
        'data' => 'gray',
        'akses' => 'info',
        'akses-data' => 'warning',
        'sistem' => 'warning',
        'izin' => 'danger',
        'ditolak' => 'danger',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function causerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * The admin who was actually at the keyboard, when this entry was made
     * while impersonating an employee.
     *
     * The entry still belongs to the employee — that is whose account acted —
     * but without this, "Budi mengubah data" would be indistinguishable from
     * an admin doing it through Budi's account. Stamped in
     * AppServiceProvider::configureActivityLog().
     */
    public function impersonatorName(): ?string
    {
        $impersonator = $this->properties?->get('impersonated_by');

        if (! is_array($impersonator)) {
            return null;
        }

        $name = $impersonator['name'] ?? null;

        return is_string($name) ? $name : null;
    }

    /**
     * What the entry is *about*, in words an admin recognises — the model's
     * own Indonesian label plus a readable title, falling back to the raw
     * class name for anything logged before a label existed.
     */
    public function subjectLabel(): ?string
    {
        if ($this->subject_type === null) {
            return null;
        }

        $label = class_basename($this->subject_type);

        if (method_exists($this->subject_type, 'activitySubjectLabel')) {
            $label = $this->subject_type::activitySubjectLabel();
        }

        $title = $this->subjectTitle();

        return $title === null ? $label.' #'.$this->subject_id : $label.' — '.$title;
    }

    /**
     * Deletions are the entries that matter most and the ones the live record
     * can no longer name — it's gone. Fall back to the values the log itself
     * captured, so "Hapus Pengguna" says *which* user instead of an id nobody
     * can look up any more.
     */
    private function subjectTitle(): ?string
    {
        $subject = $this->subject;

        if ($subject !== null && method_exists($subject, 'activitySubjectTitle')) {
            return $subject->activitySubjectTitle();
        }

        $recorded = ($this->attribute_changes['old'] ?? []) + ($this->attribute_changes['attributes'] ?? []);

        foreach (['name', 'title', 'buyer_name', 'transaction_number', 'code', 'subject'] as $attribute) {
            $value = $recorded[$attribute] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
