<?php

namespace App\Filament\App\Pages;

use App\Enums\MessengerDeliveryStatus;
use App\Models\MessengerDelivery;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
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
    protected string $view = 'filament.app.pages.messenger-tasks';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Messenger';

    protected static ?string $navigationLabel = 'Tugas Messenger';

    protected static ?string $title = 'Tugas Messenger';

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
