<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $chatId;
    public string $customerName;
    public string $preview;

    public function __construct(Chat $chat)
    {
        $this->chatId = $chat->id;
        $this->customerName = $chat->customer->name;
        $this->preview = $chat->messages()->first()?->message ?? 'New conversation started';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.chats'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewChatCreated';
    }
}
