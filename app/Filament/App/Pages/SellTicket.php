<?php

namespace App\Filament\App\Pages;

use App\Enums\TicketPaymentMethod;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use RuntimeException;
use UnitEnum;

class SellTicket extends Page
{
    protected string $view = 'filament.app.pages.sell-ticket';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Tiket Event';

    protected static ?string $navigationLabel = 'Jual Tiket';

    protected static ?string $title = 'Jual Tiket';

    public ?int $eventId = null;

    public string $buyerName = '';

    public bool $isMember = false;

    public ?string $memberReference = null;

    public ?string $paymentMethod = null;

    public ?int $lastTicketId = null;

    /**
     * @return Collection<int, Event>
     */
    #[Computed]
    public function openEvents(): Collection
    {
        return Event::query()->where('is_open', true)->orderBy('name')->get();
    }

    #[Computed]
    public function selectedEvent(): ?Event
    {
        return $this->eventId
            ? $this->openEvents()->firstWhere('id', $this->eventId)
            : null;
    }

    #[Computed]
    public function lastTicket(): ?Ticket
    {
        return $this->lastTicketId
            ? Ticket::query()->with(['event', 'soldByUser'])->find($this->lastTicketId)
            : null;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function paymentMethods(): array
    {
        return collect(TicketPaymentMethod::cases())
            ->mapWithKeys(fn (TicketPaymentMethod $method) => [$method->value => $method->label()])
            ->all();
    }

    public function sell(): void
    {
        if (! $this->eventId) {
            Notification::make()->warning()->title('Pilih event dulu.')->send();

            return;
        }

        if (trim($this->buyerName) === '') {
            Notification::make()->warning()->title('Isi nama pembeli dulu.')->send();

            return;
        }

        if (! $this->paymentMethod) {
            Notification::make()->warning()->title('Pilih metode pembayaran dulu.')->send();

            return;
        }

        if ($this->isMember && trim((string) $this->memberReference) === '') {
            Notification::make()->warning()->title('Scan barcode member dulu.')->send();

            return;
        }

        $event = Event::query()->findOrFail($this->eventId);

        /** @var User $cashier */
        $cashier = Auth::user();

        try {
            $ticket = Ticket::sellFor(
                event: $event,
                cashier: $cashier,
                buyerName: trim($this->buyerName),
                isMember: $this->isMember,
                memberReference: $this->memberReference ? trim($this->memberReference) : null,
                paymentMethod: TicketPaymentMethod::from($this->paymentMethod),
            );
        } catch (RuntimeException $exception) {
            Notification::make()->warning()->title($exception->getMessage())->send();

            return;
        }

        $this->lastTicketId = $ticket->id;

        $this->reset(['buyerName', 'isMember', 'memberReference', 'paymentMethod']);
    }

    public function nextSale(): void
    {
        $this->lastTicketId = null;
    }
}
