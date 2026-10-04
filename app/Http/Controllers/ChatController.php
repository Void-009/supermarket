<?php

namespace App\Http\Controllers;

use App\Events\NewChatCreated;
use App\Events\NewChatMessage;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user = $request->user();
        $chat = Chat::where('customer_id', $user->id)->where('status', 'open')->first();

        if (!$chat) {
            $chat = Chat::create([
                'customer_id' => $user->id,
                'status' => 'open',
            ]);
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        $message->load('user');

        if ($chat->wasRecentlyCreated) {
            broadcast(new NewChatCreated($chat));
        }

        broadcast(new NewChatMessage($message));

        return response()->json(['success' => true, 'message' => $message->fresh()->load('user')]);
    }

    public function messages(Request $request): JsonResponse
    {
        $chat = Chat::where('customer_id', $request->user()->id)->where('status', 'open')->first();
        if (!$chat) {
            return response()->json(['messages' => []]);
        }

        $chat->messages()->where('is_read', false)->where('user_id', '!=', $request->user()->id)->update(['is_read' => true]);

        return response()->json([
            'chat_id' => $chat->id,
            'messages' => $chat->messages()->with('user')->orderBy('created_at')->get()->map(fn ($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'user_id' => $m->user_id,
                'sender_name' => $m->user->name,
                'is_admin' => in_array($m->user->role, ['superadmin', 'admin', 'agent']),
                'created_at' => $m->created_at->toISOString(),
            ]),
        ]);
    }

    public function activeChat(Request $request): JsonResponse
    {
        $chat = Chat::where('customer_id', $request->user()->id)->where('status', 'open')->first();
        return response()->json(['chat' => $chat ? ['id' => $chat->id] : null]);
    }
}
