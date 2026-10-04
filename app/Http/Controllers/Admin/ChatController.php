<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatQuickResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_chat'), 403);

        $chats = Chat::with(['customer', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])->where('status', 'open')->orderByDesc(
            ChatMessage::select('created_at')->whereColumn('chat_id', 'chats.id')->latest()->limit(1)
        )->get();

        $result = $chats->map(function ($chat) {
            $unread = $chat->messages()->where('is_read', false)->where('user_id', $chat->customer_id)->count();
            return [
                'id' => $chat->id,
                'customer_name' => $chat->customer->name,
                'customer_id' => $chat->customer_id,
                'last_message' => $chat->messages->first()?->message ?? '',
                'unread' => $unread,
                'created_at' => $chat->created_at->toISOString(),
            ];
        });

        return response()->json(['chats' => $result]);
    }

    public function show(Chat $chat): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_chat'), 403);

        $chat->messages()->where('is_read', false)->where('user_id', $chat->customer_id)->update(['is_read' => true]);

        return response()->json([
            'chat' => [
                'id' => $chat->id,
                'customer_name' => $chat->customer->name,
                'customer_id' => $chat->customer_id,
                'admin_id' => $chat->admin_id,
                'status' => $chat->status,
            ],
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

    public function reply(Request $request, Chat $chat): JsonResponse
    {
        abort_unless($request->user()->hasPermission('manage_chat'), 403);

        $request->validate(['message' => 'required|string|max:2000']);

        $user = $request->user();

        if (!$chat->admin_id && $user->id !== $chat->customer_id) {
            $chat->update(['admin_id' => $user->id]);
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        $message->load('user');

        broadcast(new NewChatMessage($message));

        return response()->json(['success' => true, 'message' => [
            'id' => $message->id,
            'message' => $message->message,
            'user_id' => $message->user_id,
            'sender_name' => $message->user->name,
            'is_admin' => true,
            'created_at' => $message->created_at->toISOString(),
        ]]);
    }

    public function destroy(Chat $chat): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_chat'), 403);

        $chat->delete();
        return response()->json(['success' => true]);
    }

    public function quickResponsesPage()
    {
        abort_unless(auth()->user()->hasPermission('manage_chat'), 403);

        return view('admin.chat.quick-responses');
    }

    public function quickResponses(): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_chat'), 403);

        return response()->json(['responses' => ChatQuickResponse::orderBy('title')->get()]);
    }

    public function storeQuickResponse(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('manage_chat'), 403);

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $response = ChatQuickResponse::create($request->only('title', 'message'));

        return response()->json(['success' => true, 'response' => $response]);
    }

    public function destroyQuickResponse(ChatQuickResponse $quickResponse): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_chat'), 403);

        $quickResponse->delete();
        return response()->json(['success' => true]);
    }
}
