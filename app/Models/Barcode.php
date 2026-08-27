<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use App\Enums\BarcodeFormat;
use Database\Factories\BarcodeFactory;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * @property BarcodeFormat $format
 */
#[Fillable(['format', 'content', 'label', 'created_by'])]
class Barcode extends Model
{
    /** @use HasFactory<BarcodeFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Barcode';
    }

    protected function casts(): array
    {
        return [
            'format' => BarcodeFormat::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * PNG bytes of this barcode/QR code — generated on demand rather than
     * stored, since the same (format, content) pair always renders
     * identically and generation is cheap.
     */
    public function renderPng(): string
    {
        return match ($this->format) {
            BarcodeFormat::Qr => (new Builder(data: $this->content, size: 400, margin: 16))
                ->build()
                ->getString(),
            BarcodeFormat::Code128 => (new BarcodeGeneratorPNG)->getBarcode($this->content, BarcodeGenerator::TYPE_CODE_128),
            BarcodeFormat::Ean13 => (new BarcodeGeneratorPNG)->getBarcode($this->content, BarcodeGenerator::TYPE_EAN_13),
            BarcodeFormat::Code39 => (new BarcodeGeneratorPNG)->getBarcode($this->content, BarcodeGenerator::TYPE_CODE_39),
        };
    }
}
