<?php

namespace App\Filament\App\Pages;

use App\Enums\MessengerDeliveryStatus;
use App\Models\MessengerDelivery;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use RuntimeException;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class MessengerTasks extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.messenger-tasks';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Messenger');
    }

    public static function getNavigationLabel(): string
    {
        return __('Tugas Messenger');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Tugas Messenger');
    }

    public ?int $completingDeliveryId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('photo')
                    ->label('Foto Bukti Serah Terima')
                    ->collection('proof')
                    ->image()
                    ->maxSize(10240)
                    ->required(),
            ])
            ->statePath('data');
    }

    /**
     * @return Collection<int, MessengerDelivery>
     */
    #[Computed]
    public function openTasks(): Collection
    {
        return MessengerDelivery::query()
            ->where('status', MessengerDeliveryStatus::Available)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return Collection<int, MessengerDelivery>
     */
    #[Computed]
    public function myTasks(): Collection
    {
        return MessengerDelivery::query()
            ->where('messenger_id', Auth::id())
            ->whereIn('status', [MessengerDeliveryStatus::PickedUp, MessengerDeliveryStatus::InTransit])
            ->orderBy('claimed_at')
            ->get();
    }

    public function claim(int $deliveryId): void
    {
        /** @var User $messenger */
        $messenger = Auth::user();

        try {
            MessengerDelivery::claim($deliveryId, $messenger);
        } catch (ModelNotFoundException) {
            // Caught before RuntimeException on purpose: firstOrFail()
            // throws a subclass of it, and its own message is the
            // English "No query results for model [App\Models...]" —
            // meaningless to a cashier mid-transaction.
            Notification::make()->warning()->title('Tugas ini sudah tidak ada. Muat ulang halaman lalu coba lagi.')->send();

            return;
        } catch (RuntimeException $exception) {
            Notification::make()
                ->warning()
                ->title($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Tugas berhasil diambil')
            ->send();
    }

    public function startTransit(int $deliveryId): void
    {
        /** @var User $messenger */
        $messenger = Auth::user();

        $delivery = MessengerDelivery::query()->findOrFail($deliveryId);

        try {
            $delivery->markInTransit($messenger);
        } catch (ModelNotFoundException) {
            // Caught before RuntimeException on purpose: firstOrFail()
            // throws a subclass of it, and its own message is the
            // English "No query results for model [App\Models...]" —
            // meaningless to a cashier mid-transaction.
            Notification::make()->warning()->title('Tugas ini sudah tidak ada. Muat ulang halaman lalu coba lagi.')->send();

            return;
        } catch (RuntimeException $exception) {
            Notification::make()
                ->warning()
                ->title($exception->getMessage())
                ->send();
        }
    }

    public function startCompleting(int $deliveryId): void
    {
        $this->completingDeliveryId = $deliveryId;
        $this->form->fill();
    }

    public function cancelCompleting(): void
    {
        $this->completingDeliveryId = null;
    }

    public function completeDelivery(): void
    {
        /** @var User $messenger */
        $messenger = Auth::user();

        $delivery = MessengerDelivery::query()->findOrFail($this->completingDeliveryId);

        $this->form->getState();

        try {
            $this->form->model($delivery)->saveRelationships();
            $delivery->markDelivered($messenger);
        } catch (Throwable $exception) {
            Notification::make()
                ->warning()
                ->title($exception instanceof RuntimeException ? $exception->getMessage() : 'Gagal menandai terkirim.')
                ->send();

            return;
        }

        $this->completingDeliveryId = null;

        Notification::make()
            ->success()
            ->title('Pengiriman ditandai selesai')
            ->send();
    }
}
