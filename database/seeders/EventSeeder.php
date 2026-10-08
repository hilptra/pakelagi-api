<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Support\UniqueSlug;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Jakarta Thrift & Vintage Market 2026',
                'description' => "Bergabunglah dengan PakeLagi di acara bazaar thrift dan fashion vintage terbesar di Jakarta. Temukan lebih dari 100+ pilihan pakaian kurasi terbaik kami langsung di lokasi booth A-12.\n\nNikmati diskon eksklusif dan merchandise ramah lingkungan!",
                'event_date' => now()->addDays(7)->setTime(10, 0),
                'end_date' => now()->addDays(9)->setTime(21, 0),
                'location' => 'Senayan Park, Jakarta Pusat',
                'organizer' => 'PakeLagi x Jakarta Thrift Community',
                'status' => EventStatus::Published->value,
                'show_on_homepage' => true,
            ],
            [
                'title' => 'Bandung Sustainable Fashion Pop-Up',
                'description' => "PakeLagi hadir di Bandung! Kunjungi pop-up booth kami untuk mencoba langsung berbagai outfit preloved branded dalam kondisi mulus. Dapatkan saran styling gratis dari tim PakeLagi.",
                'event_date' => now()->addDays(20)->setTime(11, 0),
                'end_date' => now()->addDays(22)->setTime(20, 0),
                'location' => 'Cihampelas Walk, Bandung',
                'organizer' => 'PakeLagi',
                'status' => EventStatus::Published->value,
                'show_on_homepage' => true,
            ],
            [
                'title' => 'PakeLagi Preloved Weekend Market 2025',
                'description' => 'Dokumentasi acara meetup komunitas pecinta fashion berkelanjutan PakeLagi yang berlangsung sukses di Taman Mini Indonesia Indah.',
                'event_date' => now()->subMonths(2)->setTime(9, 0),
                'end_date' => now()->subMonths(2)->addDays(2)->setTime(18, 0),
                'location' => 'Taman Mini Indonesia Indah, Jakarta',
                'organizer' => 'PakeLagi',
                'status' => EventStatus::Published->value,
                'show_on_homepage' => false,
            ],
        ];

        foreach ($events as $evt) {
            Event::create([
                ...$evt,
                'slug' => UniqueSlug::make(Event::class, $evt['title'], 'event'),
            ]);
        }
    }
}
