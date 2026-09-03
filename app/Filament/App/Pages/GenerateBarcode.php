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
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

class GenerateBarcode extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.generate-barcode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tools');
    }

    public static function getNavigationLabel(): string
    {
        return __('Generator Barcode/QR');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Generator Barcode/QR');
    }

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
            Notification::make()->warning()->title(__('Pilih jenis dulu.'))->send();

            return;
        }

        if (trim($this->content) === '') {
            Notification::make()->warning()->title(__('Isi konten dulu.'))->send();

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
            // The barcode library's own message is English and technical
            // ("Barcode requires a positive length"), which reads as a crash
            // rather than "your content doesn't fit this format". Say what to
            // do instead, and leave the raw text to the log.
            report($exception);

            Notification::make()
                ->danger()
                ->title(__('Konten tidak cocok untuk jenis ini'))
                ->body(__('Cek lagi isinya — EAN-13 hanya menerima 12-13 digit angka, sedangkan Code 128 dan Code 39 bisa teks bebas.'))
                ->send();

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
