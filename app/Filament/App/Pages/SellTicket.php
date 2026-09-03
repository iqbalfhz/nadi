<?php

namespace App\Filament\App\Pages;

use App\Enums\TicketPaymentMethod;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use RuntimeException;
use UnitEnum;

class SellTicket extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.sell-ticket';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tiket Event');
    }

    public static function getNavigationLabel(): string
    {
        return __('Jual Tiket');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Jual Tiket');
    }

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
            Notification::make()->warning()->title(__('Pilih event dulu.'))->send();

            return;
        }

        if (trim($this->buyerName) === '') {
            Notification::make()->warning()->title(__('Isi nama pembeli dulu.'))->send();

            return;
        }

        if (! $this->paymentMethod) {
            Notification::make()->warning()->title(__('Pilih metode pembayaran dulu.'))->send();

            return;
        }

        if ($this->isMember && trim((string) $this->memberReference) === '') {
            Notification::make()->warning()->title(__('Scan barcode member dulu.'))->send();

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
        } catch (ModelNotFoundException) {
            // Caught before RuntimeException on purpose: firstOrFail()
            // throws a subclass of it, and its own message is the
            // English "No query results for model [App\Models...]" —
            // meaningless to a cashier mid-transaction.
            Notification::make()->warning()->title(__('Data event ini sudah tidak ada. Muat ulang halaman lalu coba lagi.'))->send();

            return;
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
