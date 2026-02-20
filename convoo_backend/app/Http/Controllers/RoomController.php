<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Public rooms OR rooms user is a member of
        $rooms = Room::where('is_private', false)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount('members')
            ->get();

        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'boolean',
        ]);

        return DB::transaction(function () use ($request) {
            $room = Room::create([
                'name' => $request->name,
                'description' => $request->description,
                'is_private' => $request->is_private ?? false,
                'owner_id' => $request->user()->id,
            ]);

            RoomMember::create([
                'room_id' => $room->id,
                'user_id' => $request->user()->id,
                'role' => 'admin',
            ]);

            return response()->json($room, 201);
        });
    }

    public function show(Room $room)
    {
        return response()->json($room->load('owner')->loadCount('members'));
    }

    public function update(Request $request, Room $room)
    {
        // Check if user is admin or owner
        $member = RoomMember::where('room_id', $room->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$member || $member->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'boolean',
            'avatar' => 'nullable|string',
        ]);

        $room->update($request->only('name', 'description', 'is_private', 'avatar'));

        return response()->json($room);
    }

    public function destroy(Request $request, Room $room)
    {
        if ($room->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $room->delete();

        return response()->json(['message' => 'Room deleted']);
    }

    public function join(Request $request, Room $room)
    {
        if ($room->is_private) {
            return response()->json(['message' => 'Cannot join private room without invitation'], 403);
        }

        $exists = RoomMember::where('room_id', $room->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already a member'], 400);
        }

        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $request->user()->id,
            'role' => 'member',
        ]);

        return response()->json(['message' => 'Joined successfully']);
    }

    public function leave(Request $request, Room $room)
    {
        $member = RoomMember::where('room_id', $room->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'Not a member'], 400);
        }

        if ($room->owner_id === $request->user()->id) {
            return response()->json(['message' => 'Owner cannot leave room. Delete it instead.'], 400);
        }

        $member->delete();

        return response()->json(['message' => 'Left successfully']);
    }

    public function invite(Request $request, Room $room)
    {
        // Only admin can invite
        $caller = RoomMember::where('room_id', $room->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$caller || $caller->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $exists = RoomMember::where('room_id', $room->id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User already a member'], 400);
        }

        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $request->user_id,
            'role' => 'member',
        ]);

        return response()->json(['message' => 'User invited successfully']);
    }

    public function kick(Request $request, Room $room, User $user)
    {
        // Only admin can kick
        $caller = RoomMember::where('room_id', $room->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$caller || $caller->role !== 'admin' || $user->id === $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $member = RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'User is not a member'], 400);
        }

        $member->delete();

        return response()->json(['message' => 'User kicked successfully']);
    }

    public function members(Room $room)
    {
        return response()->json($room->members()->with('user')->get());
    }
}
