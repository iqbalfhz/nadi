<?php

namespace App\Filament\App\Pages;

use App\Enums\BarcodeFormat;
use App\Models\Barcode;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

class GenerateBarcode extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.generate-barcode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Tools';

    protected static ?string $navigationLabel = 'Generator Barcode/QR';

    protected static ?string $title = 'Generator Barcode/QR';

    public string $format = '';

    public string $content = '';

    public string $label = '';

    public ?int $lastBarcodeId = null;

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function formats(): array
    {
        return collect(BarcodeFormat::cases())
            ->mapWithKeys(fn (BarcodeFormat $format) => [$format->value => $format->label()])
            ->all();
    }

    #[Computed]
    public function lastBarcode(): ?Barcode
    {
        return $this->lastBarcodeId
            ? Barcode::query()->find($this->lastBarcodeId)
            : null;
    }

    public function generate(): void
    {
        if ($this->format === '') {
            Notification::make()->warning()->title('Pilih jenis dulu.')->send();

            return;
        }

        if (trim($this->content) === '') {
            Notification::make()->warning()->title('Isi konten dulu.')->send();

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $barcode = new Barcode([
            'format' => BarcodeFormat::from($this->format),
            'content' => trim($this->content),
            'label' => trim($this->label) !== '' ? trim($this->label) : null,
            'created_by' => $user->id,
        ]);

        // Validate the content actually renders for the chosen format (e.g.
        // EAN-13 needs 12-13 digits) before saving a record nobody can
        // ever download.
        try {
            $barcode->renderPng();
        } catch (Throwable $exception) {
            Notification::make()->danger()->title('Gagal generate: '.$exception->getMessage())->send();

            return;
        }

        $barcode->save();

        $this->lastBarcodeId = $barcode->id;

        $this->reset(['content', 'label']);
    }

    public function generateAnother(): void
    {
        $this->lastBarcodeId = null;
    }
}
