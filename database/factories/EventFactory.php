<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'title' => $title,
            'slug' => UniqueSlug::make(Event::class, $title, 'event'),
            'description' => fake()->paragraph(),
            'event_date' => fake()->dateTimeBetween('now', '+2 months'),
            'end_date' => null,
            'location' => fake()->city(),
            'organizer' => 'PakeLagi',
            'cover_image' => null,
            'cover_image_thumbnail' => null,
            'status' => EventStatus::Published->value,
            'show_on_homepage' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Draft->value,
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_date' => now()->addDays(5),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_date' => now()->subDays(5),
            'end_date' => now()->subDays(4),
        ]);
    }

    public function homepage(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_on_homepage' => true,
        ]);
    }
}
