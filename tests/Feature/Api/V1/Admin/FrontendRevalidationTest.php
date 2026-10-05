<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Jobs\RevalidateFrontendCache;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrontendRevalidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Storage::fake('public');

        config([
            'pakelagi.frontend.revalidate_url' => 'https://frontend.test/api/revalidate',
            'pakelagi.frontend.revalidate_secret' => 'rahasia',
        ]);

        $this->admin = User::factory()->create();
    }

    /** @param  array<int, string>  $tags */
    private function assertRevalidated(array $tags): void
    {
        Bus::assertDispatched(
            RevalidateFrontendCache::class,
            fn (RevalidateFrontendCache $job) => $job->tags === $tags,
        );
    }

    private function productPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'name' => 'Kemeja Flanel',
            'price' => 85000,
            'size_label' => 'L',
            'condition' => 'good',
        ], $overrides);
    }

    private function image(Product $product, bool $primary = false, int $order = 0): ProductImage
    {
        return ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => "products/{$product->id}/{$order}.webp",
            'thumbnail_path' => "products/{$product->id}/{$order}_thumb.webp",
            'is_primary' => $primary,
            'sort_order' => $order,
        ]);
    }

    public function test_creating_a_product_revalidates_the_catalog_and_the_new_page(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products', $this->productPayload($category))
            ->assertCreated();

        $this->assertRevalidated(['catalog', 'product:kemeja-flanel']);
    }

    public function test_updating_a_product_revalidates_its_page(): void
    {
        $product = Product::factory()->create(['slug' => 'lama']);

        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/products/'.$product->id, $this->productPayload($product->category))
            ->assertOk();

        $this->assertRevalidated(['catalog', 'product:lama']);
    }

    public function test_deleting_a_product_revalidates_its_page(): void
    {
        $product = Product::factory()->create(['slug' => 'hapus-aku']);

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/products/'.$product->id)
            ->assertNoContent();

        $this->assertRevalidated(['catalog', 'product:hapus-aku']);
    }

    public function test_marking_a_product_as_sold_revalidates_its_page(): void
    {
        $product = Product::factory()->create(['slug' => 'laku']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products/'.$product->id.'/sold')
            ->assertOk();

        $this->assertRevalidated(['catalog', 'product:laku']);
    }

    public function test_marking_an_already_sold_product_does_not_revalidate(): void
    {
        $product = Product::factory()->sold()->create();

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products/'.$product->id.'/sold')
            ->assertOk();

        Bus::assertNotDispatched(RevalidateFrontendCache::class);
    }

    public function test_a_rejected_sold_request_does_not_revalidate(): void
    {
        $product = Product::factory()->hidden()->create();

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products/'.$product->id.'/sold')
            ->assertStatus(422);

        Bus::assertNotDispatched(RevalidateFrontendCache::class);
    }

    public function test_changing_the_status_revalidates_its_page(): void
    {
        $product = Product::factory()->create(['slug' => 'tarik']);

        $this->actingAs($this->admin)
            ->patchJson('/api/v1/admin/products/'.$product->id.'/status', ['status' => 'hidden'])
            ->assertOk();

        $this->assertRevalidated(['catalog', 'product:tarik']);
    }

    public function test_setting_the_same_status_does_not_revalidate(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson('/api/v1/admin/products/'.$product->id.'/status', ['status' => 'available'])
            ->assertOk();

        Bus::assertNotDispatched(RevalidateFrontendCache::class);
    }

    public function test_uploading_photos_revalidates_the_product_page(): void
    {
        $product = Product::factory()->create(['slug' => 'foto-baru']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products/'.$product->id.'/images', [
                'images' => [UploadedFile::fake()->image('a.jpg', 200, 200)],
            ])
            ->assertCreated();

        $this->assertRevalidated(['catalog', 'product:foto-baru']);
    }

    public function test_setting_a_primary_photo_revalidates_the_product_page(): void
    {
        $product = Product::factory()->create(['slug' => 'utama']);
        $this->image($product, primary: true, order: 0);
        $second = $this->image($product, order: 1);

        $this->actingAs($this->admin)
            ->patchJson('/api/v1/admin/products/'.$product->id.'/images/'.$second->id, ['is_primary' => true])
            ->assertOk();

        $this->assertRevalidated(['catalog', 'product:utama']);
    }

    public function test_reordering_photos_revalidates_the_product_page(): void
    {
        $product = Product::factory()->create(['slug' => 'urut']);
        $first = $this->image($product, primary: true, order: 0);
        $second = $this->image($product, order: 1);

        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/products/'.$product->id.'/images/order', [
                'image_ids' => [$second->id, $first->id],
            ])
            ->assertOk();

        $this->assertRevalidated(['catalog', 'product:urut']);
    }

    public function test_deleting_a_photo_revalidates_the_product_page(): void
    {
        $product = Product::factory()->create(['slug' => 'hapus-foto']);
        $image = $this->image($product, primary: true);

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/products/'.$product->id.'/images/'.$image->id)
            ->assertNoContent();

        $this->assertRevalidated(['catalog', 'product:hapus-foto']);
    }

    public function test_creating_a_category_revalidates_categories_and_the_catalog(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/categories', ['name' => 'Topi', 'measurement_fields' => ['diameter']])
            ->assertCreated();

        $this->assertRevalidated(['categories', 'catalog']);
    }

    public function test_updating_a_category_revalidates_categories_and_the_catalog(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/categories/'.$category->id, [
                'name' => 'Nama Baru',
                'measurement_fields' => ['lebar_dada'],
            ])
            ->assertOk();

        $this->assertRevalidated(['categories', 'catalog']);
    }

    public function test_deleting_a_category_revalidates_categories_and_the_catalog(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/categories/'.$category->id)
            ->assertNoContent();

        $this->assertRevalidated(['categories', 'catalog']);
    }

    public function test_failed_validation_does_not_revalidate(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products', [])
            ->assertStatus(422);

        Bus::assertNothingDispatched();
    }

    public function test_nothing_is_dispatched_when_the_frontend_is_not_configured(): void
    {
        config(['pakelagi.frontend.revalidate_url' => null]);
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products', $this->productPayload($category))
            ->assertCreated();

        Bus::assertNothingDispatched();
    }
}