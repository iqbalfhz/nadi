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

        $subject = $this->subject;

        if ($subject !== null && method_exists($subject, 'activitySubjectTitle')) {
            return $label.' — '.$subject->activitySubjectTitle();
        }

        return $label.' #'.$this->subject_id;
    }
}
