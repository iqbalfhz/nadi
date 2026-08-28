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
     * an admin recognises instead of raw strings.
     */
    public const LOG_NAMES = [
        'data' => 'Perubahan Data',
        'akses' => 'Akses & Login',
        'izin' => 'Role & Izin',
        'akses-data' => 'Lihat & Export Data',
        'sistem' => 'Sistem',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function causerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
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
