<?php

namespace App\Livewire\Queue;

use App\Models\QueueCategory;
use App\Models\QueueTicket;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class KioskTake extends Component
{
    public ?int $issuedTicketId = null;

    public function take(int $categoryId): void
    {
        $category = QueueCategory::query()->where('is_active', true)->findOrFail($categoryId);

        $ticket = QueueTicket::createNextFor($category);

        $this->issuedTicketId = $ticket->id;
    }

    public function dismissTicket(): void
    {
        $this->issuedTicketId = null;
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
    public function issuedTicket(): ?QueueTicket
    {
        return $this->issuedTicketId
            ? QueueTicket::query()->with('category')->find($this->issuedTicketId)
            : null;
    }

    public function render(): View
    {
        return view('livewire.queue.kiosk-take')
            ->layout('layouts.queue');
    }
}
