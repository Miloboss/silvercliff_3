<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Amenities ──────────────────────────────────────
        $amenityDefs = [
            ['name' => 'Air Conditioning', 'key' => 'ac',        'icon_key' => '❄️',  'sort_order' => 10],
            ['name' => 'Free Wi-Fi',        'key' => 'wifi',      'icon_key' => '📶',  'sort_order' => 20],
            ['name' => 'Private Bathroom',  'key' => 'bathroom',  'icon_key' => '🚿',  'sort_order' => 30],
            ['name' => 'Hot Water',         'key' => 'hotwater',  'icon_key' => '🌡️', 'sort_order' => 35],
            ['name' => 'Jungle View',       'key' => 'jungle',    'icon_key' => '🌿',  'sort_order' => 40],
            ['name' => 'River View',        'key' => 'river',     'icon_key' => '🌊',  'sort_order' => 50],
            ['name' => 'King Bed',          'key' => 'kingbed',   'icon_key' => '🛏️', 'sort_order' => 60],
            ['name' => 'Queen Bed',         'key' => 'queenbed',  'icon_key' => '🛏️', 'sort_order' => 65],
            ['name' => 'Twin Beds',         'key' => 'twinbeds',  'icon_key' => '🛏️', 'sort_order' => 70],
            ['name' => 'Private Terrace',   'key' => 'terrace',   'icon_key' => '🪟',  'sort_order' => 80],
            ['name' => 'Hammock',           'key' => 'hammock',   'icon_key' => '🛖',  'sort_order' => 90],
            ['name' => 'Ceiling Fan',       'key' => 'fan',       'icon_key' => '🌬️', 'sort_order' => 95],
            ['name' => 'Kettle & Coffee',   'key' => 'coffee',    'icon_key' => '☕',  'sort_order' => 100],
            ['name' => 'In-room Safe',      'key' => 'safe',      'icon_key' => '🔒',  'sort_order' => 110],
            ['name' => 'Daily Housekeeping','key' => 'housekeep', 'icon_key' => '🪣',  'sort_order' => 120],
            ['name' => 'Wraparound Deck',   'key' => 'deck',      'icon_key' => '🔮',  'sort_order' => 130],
        ];

        $amenities = [];
        foreach ($amenityDefs as $def) {
            $amenities[$def['key']] = Amenity::updateOrCreate(
                ['key' => $def['key']],
                $def
            );
        }

        // ── 2. Room Types ─────────────────────────────────────
        $roomTypes = [
            [
                'name'              => 'Deluxe Rooms',
                'slug'              => 'deluxe',
                'code_prefix'       => 'D',
                'subtitle'          => 'Spacious jungle-view rooms with all modern comforts.',
                'description'       => 'Our Deluxe Rooms are nestled among the trees, offering a perfect balance of comfort and nature. Each room features a private terrace where you can listen to the sounds of the rainforest, a king-size bed with premium linens, and a modern en-suite bathroom with hot water. Air conditioning keeps you cool during the warm tropical nights, while large windows frame the lush jungle canopy.',
                'base_price_thb'    => 1800,
                'capacity_adults'   => 2,
                'capacity_children' => 1,
                'cover_image'       => null,
                'highlights'        => [
                    ['icon' => '👥', 'label' => 'Up to 2 Adults'],
                    ['icon' => '🌿', 'label' => 'Jungle View'],
                    ['icon' => '❄️', 'label' => 'Air Conditioning'],
                    ['icon' => '🚿', 'label' => 'Private Bathroom'],
                    ['icon' => '📶', 'label' => 'Free Wi-Fi'],
                    ['icon' => '🛏️', 'label' => 'King Bed'],
                ],
                'is_active'         => true,
                'sort_order'        => 10,
                'amenity_keys'      => ['ac', 'wifi', 'bathroom', 'hotwater', 'jungle', 'kingbed', 'terrace', 'coffee', 'safe', 'housekeep'],
                'room_codes'        => ['D1', 'D2', 'D3', 'D4', 'D5'],
                'zone'              => 'D',
            ],
            [
                'name'              => 'Octagon Cottage',
                'slug'              => 'oc',
                'code_prefix'       => 'OC',
                'subtitle'          => 'Unique eight-sided cottages with panoramic forest views.',
                'description'       => 'The Octagon Cottages are our most architecturally distinctive rooms. The eight-sided design maximises natural light and provides 270° views of the surrounding jungle. Elevated on stilts, these cottages offer a true treetop experience. The open-plan interior features a queen bed, a reading nook, and a wraparound deck — perfect for watching wildlife at dawn.',
                'base_price_thb'    => 2400,
                'capacity_adults'   => 2,
                'capacity_children' => 0,
                'cover_image'       => null,
                'highlights'        => [
                    ['icon' => '👥', 'label' => 'Up to 2 Adults'],
                    ['icon' => '🌳', 'label' => 'Treetop Views'],
                    ['icon' => '❄️', 'label' => 'Air Conditioning'],
                    ['icon' => '🚿', 'label' => 'Private Bathroom'],
                    ['icon' => '🪟', 'label' => 'Wraparound Deck'],
                    ['icon' => '🔮', 'label' => 'Unique Design'],
                ],
                'is_active'         => true,
                'sort_order'        => 20,
                'amenity_keys'      => ['ac', 'wifi', 'bathroom', 'hotwater', 'jungle', 'queenbed', 'deck', 'coffee', 'housekeep'],
                'room_codes'        => ['OC1', 'OC2'],
                'zone'              => 'O',
            ],
            [
                'name'              => 'Bungalow',
                'slug'              => 'bungalow',
                'code_prefix'       => 'B',
                'subtitle'          => 'Traditional Thai-style bungalows set deep in the jungle.',
                'description'       => 'Our Bungalows capture the essence of traditional Thai jungle living. Built with natural materials and elevated on wooden platforms, each bungalow blends seamlessly into the forest. A private veranda with hammock invites you to slow down and listen to the river below. Inside, you\'ll find a comfortable double bed, ceiling fan, and a refreshing open-air bathroom.',
                'base_price_thb'    => 1400,
                'capacity_adults'   => 2,
                'capacity_children' => 2,
                'cover_image'       => null,
                'highlights'        => [
                    ['icon' => '👥', 'label' => 'Up to 4 Guests'],
                    ['icon' => '🌊', 'label' => 'River View'],
                    ['icon' => '🌬️', 'label' => 'Ceiling Fan'],
                    ['icon' => '🚿', 'label' => 'Open-air Bathroom'],
                    ['icon' => '🛖', 'label' => 'Hammock'],
                    ['icon' => '🌴', 'label' => 'Jungle Setting'],
                ],
                'is_active'         => true,
                'sort_order'        => 30,
                'amenity_keys'      => ['fan', 'wifi', 'bathroom', 'river', 'jungle', 'queenbed', 'hammock', 'housekeep'],
                'room_codes'        => ['B1', 'B2', 'B3', 'B4'],
                'zone'              => 'B',
            ],
            [
                'name'              => 'Family Rooms',
                'slug'              => 'family',
                'code_prefix'       => 'F',
                'subtitle'          => 'Generous rooms designed for families exploring the jungle together.',
                'description'       => 'Our Family Rooms are the largest accommodation at Silver Cliff, designed to give families the space they need after a day of adventure. The interconnecting layout features a king bed in the main room and twin beds in the adjoining area, all with air conditioning and blackout curtains. A large shared bathroom, extra storage, and a spacious terrace make this the perfect jungle base for families.',
                'base_price_thb'    => 2800,
                'capacity_adults'   => 2,
                'capacity_children' => 3,
                'cover_image'       => null,
                'highlights'        => [
                    ['icon' => '👨‍👩‍👧‍👦', 'label' => 'Up to 5 Guests'],
                    ['icon' => '🌿', 'label' => 'Jungle View'],
                    ['icon' => '❄️', 'label' => 'Air Conditioning'],
                    ['icon' => '🚿', 'label' => 'Large Bathroom'],
                    ['icon' => '🛏️', 'label' => 'King + Twin Beds'],
                    ['icon' => '🏡', 'label' => 'Spacious Terrace'],
                ],
                'is_active'         => true,
                'sort_order'        => 40,
                'amenity_keys'      => ['ac', 'wifi', 'bathroom', 'hotwater', 'jungle', 'kingbed', 'twinbeds', 'terrace', 'coffee', 'safe', 'housekeep'],
                'room_codes'        => ['F1', 'F2'],
                'zone'              => 'F',
            ],
        ];

        foreach ($roomTypes as $rtData) {
            $amenityKeys = $rtData['amenity_keys'];
            $roomCodes   = $rtData['room_codes'];
            $zone        = $rtData['zone'];
            unset($rtData['amenity_keys'], $rtData['room_codes'], $rtData['zone']);

            // Create or update room type
            $rt = RoomType::updateOrCreate(
                ['slug' => $rtData['slug']],
                $rtData
            );

            // Sync amenities
            $amenityIds = collect($amenityKeys)
                ->map(fn($k) => $amenities[$k]?->id)
                ->filter()
                ->values()
                ->toArray();
            $rt->amenities()->sync($amenityIds);

            // Create rooms if they don't exist, link to room type
            $sortBase = $rt->sort_order;
            foreach ($roomCodes as $i => $code) {
                $existing = Room::where('room_code', $code)->first();
                if ($existing) {
                    // Update existing room to link to this room type
                    $existing->update([
                        'room_type_id' => $rt->id,
                        'zone'         => $zone,
                        'sort_order'   => $sortBase + $i,
                    ]);
                } else {
                    Room::create([
                        'room_type_id' => $rt->id,
                        'room_code'    => $code,
                        'zone'         => $zone,
                        'sort_order'   => $sortBase + $i,
                        'is_active'    => true,
                    ]);
                }
            }
        }
    }
}
