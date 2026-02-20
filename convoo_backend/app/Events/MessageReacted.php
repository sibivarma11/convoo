<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\Message;
use App\Models\User;

class MessageReacted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message_id;
    public $emoji;
    public $action;
    public $reactions;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct($message_id, $emoji, $action, $reactions, User $user)
    {
        $this->message_id = $message_id;
        $this->emoji = $emoji;
        $this->action = $action;
        $this->reactions = $reactions;
        $this->user = $user->only(['id', 'name', 'avatar']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $message = Message::find($this->message_id);
        return [
            new PresenceChannel('room.' . $message->room_id),
        ];
    }

    public function broadcastAs()
    {
        return 'message.reacted';
    }
}
