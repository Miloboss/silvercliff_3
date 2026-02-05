<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            // D Block
            ['room_code' => 'D1', 'zone' => 'D', 'sort_order' => 10],
            ['room_code' => 'D2', 'zone' => 'D', 'sort_order' => 11],
            ['room_code' => 'D3', 'zone' => 'D', 'sort_order' => 12],
            ['room_code' => 'D4', 'zone' => 'D', 'sort_order' => 13],
            ['room_code' => 'D5', 'zone' => 'D', 'sort_order' => 14],
            
            // B Block
            ['room_code' => 'B1', 'zone' => 'B', 'sort_order' => 20],
            ['room_code' => 'B2', 'zone' => 'B', 'sort_order' => 21],
            ['room_code' => 'B3', 'zone' => 'B', 'sort_order' => 22],
            ['room_code' => 'B4', 'zone' => 'B', 'sort_order' => 23],

            // O Block
            ['room_code' => 'O1', 'zone' => 'O', 'sort_order' => 30],
            ['room_code' => 'O2', 'zone' => 'O', 'sort_order' => 31],

            // F Block
            ['room_code' => 'F7', 'zone' => 'F', 'sort_order' => 40],
            ['room_code' => 'F8', 'zone' => 'F', 'sort_order' => 41],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
