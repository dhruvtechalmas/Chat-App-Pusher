<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupUnreadMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $groupId;
    public $unreadMessageCount;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($groupId, $unreadMessageCount, $message = null)
    {
        $this->groupId = $groupId;
        $this->unreadMessageCount = $unreadMessageCount;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
                new PrivateChannel('group-unread'),
            ];
    }

    public function broadcastWith()
    {
        return [
            'groupId' => $this->groupId,
            'unreadMessageCount' => $this->unreadMessageCount,
            'message' => $this->message,
        ];
    }
}
