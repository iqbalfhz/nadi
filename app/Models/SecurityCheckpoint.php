<?php

namespace App\Models;

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
    use HasFactory;

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
