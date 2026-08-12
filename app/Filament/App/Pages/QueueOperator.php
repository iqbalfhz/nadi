<?php

namespace App\Filament\App\Pages;

use App\Enums\QueueTicketStatus;
use App\Events\QueueNumberCalled;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class QueueOperator extends Page
{
    protected string $view = 'filament.app.pages.queue-operator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSpeakerWave;

    protected static ?string $navigationLabel = 'Operator Antrian';

    protected static ?string $title = 'Operator Antrian';

    public ?int $categoryId = null;

    public string $counterLabel = '';

    public ?int $currentTicketId = null;

    public function mount(): void
    {
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
        if (! $this->categoryId || $this->currentTicketId || trim($this->counterLabel) === '') {
            return;
        }

        $category = QueueCategory::query()->findOrFail($this->categoryId);

        /** @var User $operator */
        $operator = Auth::user();

        $ticket = QueueTicket::callNextFor($category, $operator, trim($this->counterLabel));

        if (! $ticket) {
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
