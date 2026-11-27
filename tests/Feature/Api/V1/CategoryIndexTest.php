<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class CategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(Category::PUBLIC_CACHE_KEY);
    }

    public function test_it_lists_categories_with_measurement_fields(): void
    {
        Category::factory()->create([
            'name' => 'Atasan',
            'slug' => 'atasan',
            'measurement_fields' => ['lebar_dada', 'panjang_baju'],
        ]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'atasan')
            ->assertJsonPath('data.0.measurement_fields', ['lebar_dada', 'panjang_baju']);
    }

    public function test_subsequent_requests_use_public_categories_cache(): void
    {
        Category::factory()->create([
            'name' => 'Atasan',
            'slug' => 'atasan',
            'measurement_fields' => ['lebar_dada'],
        ]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertTrue(Cache::has(Category::PUBLIC_CACHE_KEY));

        // Insert directly without cache invalidation to verify next request reads from cache.
        DB::table('categories')->insert([
            'name' => 'Bawahan',
            'slug' => 'bawahan',
            'measurement_fields' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'atasan');
    }

    public function test_admin_store_invalidates_public_categories_cache(): void
    {
        $admin = User::factory()->create();
        Category::factory()->create(['name' => 'Atasan', 'slug' => 'atasan']);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Outer',
                'measurement_fields' => ['lebar_dada'],
            ])
            ->assertCreated();

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.slug', 'outer');
    }

    public function test_admin_update_invalidates_public_categories_cache(): void
    {
        $admin = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Atasan', 'slug' => 'atasan']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Atasan');

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'name' => 'Atasan Baru',
                'measurement_fields' => ['lebar_dada'],
            ])
            ->assertOk();

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Atasan Baru');
    }

    public function test_admin_delete_invalidates_public_categories_cache(): void
    {
        $admin = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Atasan', 'slug' => 'atasan']);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertNoContent();

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_public_categories_falls_back_to_database_when_cache_fails(): void
    {
        Category::factory()->create([
            'name' => 'Atasan',
            'slug' => 'atasan',
            'measurement_fields' => ['lebar_dada'],
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with(Category::PUBLIC_CACHE_KEY)
            ->andThrow(new RuntimeException('Cache read failed'));

        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new RuntimeException('Cache write failed'));

        Log::shouldReceive('warning')->twice();

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'atasan');
    }
}
