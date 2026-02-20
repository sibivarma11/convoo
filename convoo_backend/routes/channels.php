<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    if (!$user) return false;

    $room = \App\Models\Room::find($roomId);
    if (!$room) return false;

    // Check if user is a member of the room
    $isMember = \App\Models\RoomMember::where('room_id', $roomId)
        ->where('user_id', $user->id)
        ->exists();

    if ($room->is_private && !$isMember) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name, 'avatar' => $user->avatar];
});
