<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_index_returns_published_events_only(): void
    {
        Event::factory()->upcoming()->create(['title' => 'Upcoming Public Event']);
        Event::factory()->past()->create(['title' => 'Past Public Event']);
        Event::factory()->draft()->create(['title' => 'Draft Event']);

        $response = $this->getJson('/api/v1/events')->assertOk();

        $response->assertJsonCount(2, 'data');
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Upcoming Public Event'));
        $this->assertTrue($titles->contains('Past Public Event'));
        $this->assertFalse($titles->contains('Draft Event'));
    }

    public function test_public_index_filters_upcoming_and_past(): void
    {
        Event::factory()->upcoming()->create(['title' => 'Future Event']);
        Event::factory()->past()->create(['title' => 'Old Event']);

        $upcomingRes = $this->getJson('/api/v1/events?type=upcoming')->assertOk();
        $upcomingRes->assertJsonCount(1, 'data');
        $this->assertEquals('Future Event', $upcomingRes->json('data.0.title'));

        $pastRes = $this->getJson('/api/v1/events?type=past')->assertOk();
        $pastRes->assertJsonCount(1, 'data');
        $this->assertEquals('Old Event', $pastRes->json('data.0.title'));
    }

    public function test_public_show_returns_event_by_slug(): void
    {
        $event = Event::factory()->upcoming()->create(['slug' => 'jakarta-thrift-2026']);

        $this->getJson('/api/v1/events/jakarta-thrift-2026')
            ->assertOk()
            ->assertJsonPath('data.slug', 'jakarta-thrift-2026');
    }

    public function test_admin_can_create_event_with_cover_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/events', [
                'title' => 'Jakarta Pop-up Market',
                'description' => 'Event fashion preloved terbesar',
                'event_date' => now()->addDays(10)->toDateTimeString(),
                'location' => 'Senayan Park',
                'organizer' => 'PakeLagi',
                'status' => 'published',
                'show_on_homepage' => true,
                'cover_image' => UploadedFile::fake()->image('cover.jpg', 800, 600),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('events', [
            'title' => 'Jakarta Pop-up Market',
            'slug' => 'jakarta-pop-up-market',
            'location' => 'Senayan Park',
            'status' => 'published',
            'show_on_homepage' => true,
        ]);

        $this->assertNotNull($response->json('data.cover_image_url'));
    }

    public function test_admin_can_update_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create(['title' => 'Original Event']);

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/events/{$event->id}", [
                'title' => 'Updated Event Title',
                'description' => $event->description,
                'event_date' => $event->event_date->toDateTimeString(),
                'location' => $event->location,
                'status' => EventStatus::Published->value,
            ])
            ->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
            'slug' => $event->slug, // slug remains unchanged
        ]);
    }

    public function test_admin_can_delete_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/events/{$event->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
