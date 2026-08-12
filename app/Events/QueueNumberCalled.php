<?php

namespace App\Events;

use App\Models\QueueTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class QueueNumberCalled implements ShouldBroadcast
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
