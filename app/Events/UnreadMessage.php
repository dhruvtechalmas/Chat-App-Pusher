<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnreadMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender_id;
    public $receiver_id;
    public $unreadMessageCount;
    /**
     * Create a new event instance.
     */
    public function __construct($sender_id , $receiver_id , $unreadMessageCount )
    {
        $this->sender_id = $sender_id;
        $this->receiver_id = $receiver_id;
        $this->unreadMessageCount = $unreadMessageCount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('unread-channel.' . $this->receiver_id),
        ];
    }
    
    public function broadcastWith()
    {
        return [
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'unreadMessageCount' => $this->unreadMessageCount,
        ];
    }

}
