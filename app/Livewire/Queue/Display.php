<?php

namespace App\Livewire\Queue;

use App\Models\QueueCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Display extends Component
{
    /**
     * @return Collection<int, QueueCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return QueueCategory::query()
            ->where('is_active', true)
            ->with('latestCalledTicket')
            ->orderBy('name')
            ->get();
    }

    /**
     * Empty on purpose — its only job is to make Livewire re-render this
     * component (re-fetching $this->categories) whenever a number is called.
     * The voice announcement itself is wired directly in the Blade view via
     * a plain Echo listener, so it fires with zero server round-trip delay.
     */
    #[On('echo:queue-display,.number.called')]
    public function onNumberCalled(): void {}

    public function render(): View
    {
        return view('livewire.queue.display')
            ->layout('layouts.queue');
    }
}
