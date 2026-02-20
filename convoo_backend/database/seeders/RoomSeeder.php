<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@convoo.com'],
            [
                'name' => 'Convoo Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );

        $rooms = [
            [
                'name' => 'General Chat',
                'description' => 'The place for everyone to talk about anything.',
                'is_private' => false,
                'owner_id' => $admin->id,
            ],
            [
                'name' => 'Development',
                'description' => 'Tech talk, bugs, and feature ideas.',
                'is_private' => false,
                'owner_id' => $admin->id,
            ],
            [
                'name' => 'Design',
                'description' => 'Pixels, vectors, and aesthetic UI/UX.',
                'is_private' => false,
                'owner_id' => $admin->id,
            ],
            [
                'name' => 'Random',
                'description' => 'Memes, links, and casual banter.',
                'is_private' => false,
                'owner_id' => $admin->id,
            ],
        ];

        foreach ($rooms as $roomData) {
            \App\Models\Room::firstOrCreate(['name' => $roomData['name']], $roomData);
        }
    }
}
