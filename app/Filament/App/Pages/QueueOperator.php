<?php

namespace App\Filament\App\Pages;

use App\Enums\QueueTicketStatus;
use App\Events\QueueNumberCalled;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class QueueOperator extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.queue-operator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSpeakerWave;

    public static function getNavigationLabel(): string
    {
        return __('Operator Antrian');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Operator Antrian');
    }

    public ?int $categoryId = null;

    public string $counterLabel = '';

    public ?int $currentTicketId = null;

    public function mount(): void
    {
        // A page refresh (or a dropped connection reconnecting) used to lose
        // track of the ticket this operator was serving — the ticket stayed
        // stuck as "Called" in the database with no way to resolve it, since
        // the ticket history in /admin is read-only. Restore it here instead.
        $activeTicket = QueueTicket::query()
            ->where('called_by', Auth::id())
            ->where('status', QueueTicketStatus::Called)
            ->latest('called_at')
            ->first();

        if ($activeTicket) {
            $this->categoryId = $activeTicket->queue_category_id;
            $this->currentTicketId = $activeTicket->id;
            $this->counterLabel = $activeTicket->counter_label ?? '';

            return;
        }

        $this->categoryId = QueueCategory::query()->where('is_active', true)->orderBy('name')->value('id');
    }

    /**
     * @return Collection<int, QueueCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return QueueCategory::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function currentTicket(): ?QueueTicket
    {
        return $this->currentTicketId
            ? QueueTicket::query()->with('category')->find($this->currentTicketId)
            : null;
    }

    #[Computed]
    public function waitingCount(): int
    {
        if (! $this->categoryId) {
            return 0;
        }

        return QueueTicket::query()
            ->where('queue_category_id', $this->categoryId)
            ->where('status', QueueTicketStatus::Waiting)
            ->count();
    }

    public function callNext(): void
    {
        if (! $this->categoryId) {
            return;
        }

        if ($this->currentTicketId) {
            Notification::make()
                ->title(__('Selesaikan dulu tiket yang sedang dilayani sebelum memanggil berikutnya.'))
                ->warning()
                ->send();

            return;
        }

        if (trim($this->counterLabel) === '') {
            Notification::make()
                ->title(__('Isi dulu Nama Loket / Counter Anda.'))
                ->warning()
                ->send();

            return;
        }

        $category = QueueCategory::query()->findOrFail($this->categoryId);

        /** @var User $operator */
        $operator = Auth::user();

        $ticket = QueueTicket::callNextFor($category, $operator, trim($this->counterLabel));

        if (! $ticket) {
            Notification::make()
                ->title(__('Tidak ada lagi yang menunggu di loket ini.'))
                ->info()
                ->send();

            return;
        }

        $this->currentTicketId = $ticket->id;

        broadcast(new QueueNumberCalled($ticket));
    }

    public function recall(): void
    {
        $ticket = $this->currentTicket();

        if (! $ticket) {
            return;
        }

        broadcast(new QueueNumberCalled($ticket));
    }

    public function markDone(): void
    {
        $this->currentTicket()?->update([
            'status' => QueueTicketStatus::Done,
            'done_at' => Carbon::now(),
        ]);

        $this->currentTicketId = null;
    }

    public function markSkipped(): void
    {
        $this->currentTicket()?->update([
            'status' => QueueTicketStatus::Skipped,
            'done_at' => Carbon::now(),
        ]);

        $this->currentTicketId = null;
    }
}
