<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'name',
        'description',
        'avatar',
        'is_private',
        'owner_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(RoomMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'room_members');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
