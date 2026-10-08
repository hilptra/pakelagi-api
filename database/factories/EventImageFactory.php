<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventImage>
 */
class EventImageFactory extends Factory
{
    protected $model = EventImage::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'path' => 'events/test.webp',
            'thumbnail_path' => 'events/test_thumb.webp',
            'caption' => fake()->sentence(),
            'sort_order' => 1,
        ];
    }
}
