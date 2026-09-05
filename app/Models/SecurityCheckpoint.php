<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\SecurityCheckpointFactory;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable(['name', 'is_active'])]
class SecurityCheckpoint extends Model
{
    /** @use HasFactory<SecurityCheckpointFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Titik Patroli';
    }

    protected static function booted(): void
    {
        static::creating(function (self $checkpoint): void {
            $checkpoint->code ??= Str::random(32);
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SecurityPatrol, $this>
     */
    public function patrols(): HasMany
    {
        return $this->hasMany(SecurityPatrol::class);
    }

    /**
     * The URL printed as this checkpoint's QR code — scanning it with a
     * phone's camera opens the patrol-report form for this exact checkpoint,
     * with no manual area selection needed.
     *
     * @return Attribute<string, never>
     */
    protected function scanUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => URL::to("/app/security-scan/{$this->code}"),
        );
    }

    /**
     * PNG bytes of a QR code encoding this checkpoint's scan URL, meant to be
     * printed and stuck at the physical location.
     */
    /**
     * The checkpoint code inside whatever a scanner just read.
     *
     * The sticker holds scan_url, not the bare code, so that a guard
     * without the app can point a stock camera at it and land on the web
     * form. That is deliberate — but it means every scanner gets a URL,
     * and the mobile app would otherwise have to parse a format this class
     * owns. Parsing it here keeps the two from drifting apart: change
     * scan_url and this moves with it.
     *
     * Accepts a bare code too, so a future sticker printed either way works.
     */
    public static function codeFromScan(string $scanned): string
    {
        $scanned = trim($scanned);

        // Codes are 32 alphanumeric characters (Str::random strips /, + and
        // =), so anything shaped like one is already the answer.
        if (preg_match('/^[A-Za-z0-9]+$/', $scanned) === 1) {
            return $scanned;
        }

        $path = parse_url($scanned, PHP_URL_PATH);

        if (! is_string($path)) {
            return $scanned;
        }

        $segments = array_values(array_filter(
            explode('/', $path),
            static fn (string $segment): bool => $segment !== '',
        ));

        return $segments === [] ? $scanned : $segments[count($segments) - 1];
    }

    public function qrCodePng(): string
    {
        return (new Builder(
            data: $this->scan_url,
            size: 400,
            margin: 16,
            labelText: $this->name,
        ))->build()->getString();
    }
}
