<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Records who changed what on a model, in the shape this app's Riwayat
 * Aktivitas page reads back.
 *
 * Wraps Spatie's LogsActivity with one set of decisions rather than
 * repeating them on two dozen models:
 *
 *  - Only attributes that actually changed are stored, and an "update" that
 *    changed nothing meaningful is not written at all — otherwise every
 *    touch() and every form re-save would add a row saying nothing.
 *  - Sensitive attributes are stripped globally in config/activitylog.php,
 *    not per model, so a new model can't forget to redact a password.
 *  - Descriptions are Indonesian, matching the rest of the admin UI.
 *
 * A model that is written many times a day (queue tickets, ticket sales,
 * bazaar line items) should also set:
 *
 *     protected static array $recordEvents = ['updated', 'deleted'];
 *
 * Creation there is already recorded by the row itself — it carries who and
 * when — so logging it again only buries the edits worth reading.
 */
trait LogsNadiActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('data');
    }

    /**
     * Reads as "Ubah Ruangan", "Hapus Kios" in the activity list. The subject
     * itself is stored alongside, so the label only has to name the action
     * and the kind of record.
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $action = match ($eventName) {
            'created' => 'Tambah',
            'updated' => 'Ubah',
            'deleted' => 'Hapus',
            'restored' => 'Pulihkan',
            default => $eventName,
        };

        return $action.' '.static::activitySubjectLabel();
    }

    /**
     * Overridable per model where the class name isn't what an admin calls
     * it — "Kios" rather than "Vendor", "Penjualan Bazar" rather than
     * "VendorSale".
     */
    public static function activitySubjectLabel(): string
    {
        return class_basename(static::class);
    }

    /**
     * A short, human label for one record, shown next to the action so the
     * list reads "Ubah Event — Nobar 17an" instead of just an id.
     *
     * The first of these attributes the model actually has wins; models
     * name their headline field differently and none of them has all of
     * these.
     */
    public function activitySubjectTitle(): string
    {
        foreach (['name', 'title', 'buyer_name', 'transaction_number', 'code', 'subject'] as $attribute) {
            $value = $this->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#'.$this->getKey();
    }
}
