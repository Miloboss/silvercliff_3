<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class BookingEmailSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'resort_name'     => ['label' => 'Resort Name',     'value' => 'Silver Cliff Resort', 'group' => 'contact', 'type' => 'text'],
            'resort_email'    => ['label' => 'Resort Email',    'value' => 'silvercliff_resort@hotmail.com', 'group' => 'contact', 'type' => 'email'],
            'resort_whatsapp' => ['label' => 'Resort WhatsApp', 'value' => '+66 84 845 3550', 'group' => 'contact', 'type' => 'text'],
            'resort_phone'    => ['label' => 'Resort Phone',    'value' => '+66 84 845 3550', 'group' => 'contact', 'type' => 'text'],
            'resort_address'  => ['label' => 'Resort Address',  'value' => 'Khao Sok, Surat Thani, Thailand', 'group' => 'contact', 'type' => 'textarea'],
        ];

        foreach ($settings as $key => $data) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                $data
            );
        }
    }
}
