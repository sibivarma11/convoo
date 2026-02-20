<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use App\Models\Room;

class MediaController extends Controller
{
    public function upload(Request $request, Room $room)
    {
        // Check if member
        if (!$room->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
            'type' => 'required|string|in:image,audio,video,file',
        ]);

        $file = $request->file('file');
        $path = $file->store('media/' . $room->id, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'type' => $request->type,
            'name' => $file->getClientOriginalName(),
        ]);
    }

    public function presign(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'type' => 'required|string',
        ]);

        // Logic for S3/R2 presigned URL if using AWS
        // For local development, we might just return a local path or placeholder
        // return response()->json(['url' => ..., 'fields' => ...]);
        
        return response()->json(['message' => 'Presigned URLs not configured. Use direct upload for now.'], 501);
    }
}
