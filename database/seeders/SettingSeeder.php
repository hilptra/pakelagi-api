<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Menggunakan firstOrCreate agar nilai yang sudah diubah admin tidak tertimpa saat seeder dijalankan ulang.
     */
    public function run(): void
    {
        Setting::firstOrCreate(
            ['key' => 'admin_whatsapp'],
            ['value' => '6289501085225']
        );
    }
}

