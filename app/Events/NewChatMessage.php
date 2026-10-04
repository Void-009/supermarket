<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $chatId;
    public array $message;

    public function __construct(ChatMessage $chatMessage)
    {
        $this->chatId = $chatMessage->chat_id;
        $this->message = [
            'id' => $chatMessage->id,
            'message' => $chatMessage->message,
            'user_id' => $chatMessage->user_id,
            'sender_name' => $chatMessage->user->name,
            'is_admin' => in_array($chatMessage->user->role, ['superadmin', 'admin', 'agent']),
            'created_at' => $chatMessage->created_at->toISOString(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.{$this->chatId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewChatMessage';
    }
}
