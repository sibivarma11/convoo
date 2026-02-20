<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Message;
use App\Models\Room;
use App\Events\MessageSent;
use App\Events\MessageReacted;
use App\Events\UserTyping;

class MessageController extends Controller
{
    public function index(Request $request, Room $room)
    {
        // Check if member or public
        if ($room->is_private && !$room->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $room->messages()
            ->with(['user', 'replies'])
            ->latest()
            ->paginate(50);

        return response()->json($messages);
    }

    public function store(Request $request, Room $room)
    {
        // Check if member
        $isMember = $room->members()->where('user_id', $request->user()->id)->exists();

        if ($room->is_private && !$isMember) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Auto-join public rooms
        if (!$isMember) {
            $room->members()->create([
                'user_id' => $request->user()->id,
                'role' => 'member',
            ]);
        }

        $request->validate([
            'content' => 'required_without:media_url|string',
            'type' => 'required|string|in:text,image,audio,video,file',
            'media_url' => 'nullable|string',
            'media_duration' => 'nullable|integer',
            'media_thumbnail' => 'nullable|string',
            'reply_to' => 'nullable|exists:messages,id',
        ]);

        $message = $room->messages()->create([
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'content' => $request->content,
            'media_url' => $request->media_url,
            'media_duration' => $request->media_duration,
            'media_thumbnail' => $request->media_thumbnail,
            'reply_to' => $request->reply_to,
        ]);

        $message->load('user');

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    public function update(Request $request, Room $room, Message $message)
    {
        if ($message->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $message->update([
            'content' => $request->content,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }

    public function destroy(Request $request, Room $room, Message $message)
    {
        if ($message->user_id !== $request->user()->id && $room->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted']);
    }

    public function react(Request $request, Room $room, Message $message)
    {
        // Check if member
        $isMember = $room->members()->where('user_id', $request->user()->id)->exists();

        if ($room->is_private && !$isMember) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Auto-join public rooms
        if (!$isMember) {
            $room->members()->create([
                'user_id' => $request->user()->id,
                'role' => 'member',
            ]);
        }

        $request->validate([
            'emoji' => 'required|string',
        ]);

        $reactions = $message->reactions ?? [];
        $userId = $request->user()->id;
        $emoji = $request->emoji;
        $action = 'added';

        if (isset($reactions[$emoji]) && in_array($userId, $reactions[$emoji])) {
            $reactions[$emoji] = array_values(array_diff($reactions[$emoji], [$userId]));
            if (empty($reactions[$emoji])) {
                unset($reactions[$emoji]);
            }
            $action = 'removed';
        } else {
            if (!isset($reactions[$emoji])) {
                $reactions[$emoji] = [];
            }
            $reactions[$emoji][] = $userId;
        }

        $message->update(['reactions' => $reactions]);

        broadcast(new MessageReacted($message->id, $emoji, $action, $reactions, $request->user()))->toOthers();

        return response()->json([
            'message_id' => $message->id,
            'reactions' => $reactions,
        ]);
    }

    public function typing(Request $request, Room $room)
    {
        // Check if member
        $isMember = $room->members()->where('user_id', $request->user()->id)->exists();

        if ($room->is_private && !$isMember) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        broadcast(new UserTyping($request->user()->id, $request->user()->name, $request->is_typing, $room->id))->toOthers();

        return response()->json(['status' => 'ok']);
    }

    public function markAsRead(Request $request, Room $room)
    {
        // For simplicity, we might not track individual read receipts in DB yet
        // but can broadcast an event if needed.
        return response()->json(['message' => 'Messages marked as read']);
    }
}
