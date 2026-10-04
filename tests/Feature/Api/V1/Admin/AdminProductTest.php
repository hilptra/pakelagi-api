<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/admin/products';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    private function category(): Category
    {
        return Category::factory()->create([
            'measurement_fields' => ['lebar_dada', 'panjang_baju', 'panjang_lengan'],
        ]);
    }

    private function payload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'name' => 'Kemeja Flanel Merah',
            'description' => 'Bahan tebal dan nyaman.',
            'price' => 85000,
            'brand' => 'Uniqlo',
            'size_label' => 'L',
            'condition' => 'good',
            'condition_notes' => 'Ada noda kecil di lengan.',
            'measurements' => ['lebar_dada' => 55, 'panjang_baju' => 72],
        ], $overrides);
    }

    public function test_admin_endpoints_require_authentication(): void
    {
        $product = Product::factory()->create();
        $url = self::BASE.'/'.$product->id;

        $this->getJson(self::BASE)->assertUnauthorized();
        $this->postJson(self::BASE, [])->assertUnauthorized();
        $this->getJson($url)->assertUnauthorized();
        $this->putJson($url, [])->assertUnauthorized();
        $this->deleteJson($url)->assertUnauthorized();
        $this->postJson($url.'/sold')->assertUnauthorized();
        $this->patchJson($url.'/status', ['status' => 'hidden'])->assertUnauthorized();
    }

    public function test_admin_list_includes_hidden_and_sold_products(): void
    {
        Product::factory()->create();
        Product::factory()->sold()->create();
        Product::factory()->hidden()->create();

        $this->actingAs($this->admin)
            ->getJson(self::BASE)
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_list_can_filter_by_status(): void
    {
        Product::factory()->create();
        Product::factory()->hidden()->create(['slug' => 'draf']);

        $this->actingAs($this->admin)
            ->getJson(self::BASE.'?status=hidden')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'draf');
    }

    public function test_it_creates_a_product_as_hidden_draft_by_default(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload($category))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'kemeja-flanel-merah')
            ->assertJsonPath('data.status', 'hidden')
            ->assertJsonPath('data.measurements.lebar_dada', 55);

        $this->assertDatabaseHas('products', ['slug' => 'kemeja-flanel-merah', 'price' => 85000]);
    }

    public function test_it_can_create_a_product_that_is_published_immediately(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload($category, ['status' => 'available']))
            ->assertCreated()
            ->assertJsonPath('data.status', 'available');
    }

    public function test_slug_is_unique_when_names_collide(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)->postJson(self::BASE, $this->payload($category))->assertCreated();

        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload($category))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'kemeja-flanel-merah-2');
    }

    public function test_create_requires_mandatory_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(self::BASE, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'price', 'size_label', 'condition']);
    }

    public function test_create_rejects_invalid_values(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload($category, [
                'category_id' => 9999,
                'price' => -5,
                'condition' => 'rusak',
                'status' => 'sold',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'price', 'condition', 'status']);
    }

    public function test_create_rejects_measurements_not_defined_by_the_category(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload($category, ['measurements' => ['lingkar_pinggang' => 40]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('measurements');
    }

    public function test_admin_can_view_a_hidden_product(): void
    {
        $product = Product::factory()->hidden()->create();

        $this->actingAs($this->admin)
            ->getJson(self::BASE.'/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'hidden');
    }

    public function test_viewing_unknown_product_returns_not_found(): void
    {
        $this->actingAs($this->admin)->getJson(self::BASE.'/9999')->assertNotFound();
    }

    public function test_update_replaces_data_but_keeps_slug_and_status(): void
    {
        $category = $this->category();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'slug-lama',
            'name' => 'Nama Lama',
            'status' => ProductStatus::Available,
        ]);

        $this->actingAs($this->admin)
            ->putJson(self::BASE.'/'.$product->id, $this->payload($category, [
                'name' => 'Nama Baru',
                'status' => 'sold',
            ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Nama Baru')
            ->assertJsonPath('data.slug', 'slug-lama')
            ->assertJsonPath('data.status', 'available');
    }

    public function test_update_validates_measurements_against_category(): void
    {
        $category = $this->category();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->putJson(self::BASE.'/'.$product->id, $this->payload($category, ['measurements' => ['lebar_paha' => 30]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('measurements');
    }

    public function test_delete_removes_product_and_image_files(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        Storage::disk('public')->put('products/foto.jpg', 'isi');
        ProductImage::factory()->create(['product_id' => $product->id, 'path' => 'products/foto.jpg']);

        $this->actingAs($this->admin)
            ->deleteJson(self::BASE.'/'.$product->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
        Storage::disk('public')->assertMissing('products/foto.jpg');
    }

    public function test_it_marks_a_product_as_sold(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(self::BASE.'/'.$product->id.'/sold')
            ->assertOk()
            ->assertJsonPath('data.status', 'sold');

        $this->assertNotNull($product->fresh()->sold_at);
    }

    public function test_marking_sold_twice_keeps_the_original_sold_at(): void
    {
        $product = Product::factory()->sold()->create(['sold_at' => now()->subDays(3)]);
        $before = $product->fresh()->sold_at->toDateTimeString();

        $this->actingAs($this->admin)
            ->postJson(self::BASE.'/'.$product->id.'/sold')
            ->assertOk();

        $this->assertSame($before, $product->fresh()->sold_at->toDateTimeString());
    }

    public function test_hidden_product_cannot_be_marked_sold(): void
    {
        $product = Product::factory()->hidden()->create();

        $this->actingAs($this->admin)
            ->postJson(self::BASE.'/'.$product->id.'/sold')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame(ProductStatus::Hidden, $product->fresh()->status);
    }

    public function test_status_can_be_changed_to_hidden(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson(self::BASE.'/'.$product->id.'/status', ['status' => 'hidden'])
            ->assertOk()
            ->assertJsonPath('data.status', 'hidden');
    }

    public function test_republishing_clears_sold_at(): void
    {
        $product = Product::factory()->sold()->create();

        $this->actingAs($this->admin)
            ->patchJson(self::BASE.'/'.$product->id.'/status', ['status' => 'available'])
            ->assertOk()
            ->assertJsonPath('data.status', 'available');

        $this->assertNull($product->fresh()->sold_at);
    }

    public function test_status_endpoint_rejects_sold(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson(self::BASE.'/'.$product->id.'/status', ['status' => 'sold'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }
}
