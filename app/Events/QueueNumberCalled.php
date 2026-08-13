<?php

namespace App\Events;

use App\Models\QueueTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

// ShouldBroadcastNow (not ShouldBroadcast) — this must reach the display screen
// the instant an operator calls a number, and a queued broadcast would silently
// sit in the `jobs` table doing nothing until a `queue:work` process happens to
// be running. Reverb itself still has to be running for it to go anywhere.
class QueueNumberCalled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public string $categoryName;

    public string $formattedNumber;

    public ?string $counterLabel;

    public function __construct(QueueTicket $ticket)
    {
        $this->categoryName = $ticket->category->name;
        $this->formattedNumber = $ticket->formatted_number;
        $this->counterLabel = $ticket->counter_label;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Public channel — the waiting-room display has no login to authorize
        // a private channel against, and there's nothing sensitive in the payload.
        return [
            new Channel('queue-display'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'number.called';
    }
}
