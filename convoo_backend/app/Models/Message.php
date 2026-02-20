<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'type',
        'content',
        'media_url',
        'media_duration',
        'media_thumbnail',
        'reactions',
        'reply_to',
    ];

    protected $casts = [
        'reactions' => 'array',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to');
    }

    public function parent()
    {
        return $this->belongsTo(Message::class, 'reply_to');
    }
}
