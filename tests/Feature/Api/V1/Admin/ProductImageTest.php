<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create();
        $this->product = Product::factory()->create();
    }

    private function url(string $suffix = ''): string
    {
        return '/api/v1/admin/products/'.$this->product->id.'/images'.$suffix;
    }

    /** Membuat foto yang sudah ada (data + file palsu di storage). */
    private function existingImage(?Product $product = null, bool $primary = false, int $order = 0): ProductImage
    {
        $product ??= $this->product;
        $path = 'products/'.$product->id.'/'.Str::uuid().'.webp';
        $thumbnail = str_replace('.webp', '_thumb.webp', $path);

        Storage::disk('public')->put($path, 'foto');
        Storage::disk('public')->put($thumbnail, 'thumb');

        return ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => $path,
            'thumbnail_path' => $thumbnail,
            'is_primary' => $primary,
            'sort_order' => $order,
        ]);
    }

    public function test_endpoints_require_authentication(): void
    {
        $image = $this->existingImage();

        $this->postJson($this->url())->assertUnauthorized();
        $this->putJson($this->url('/order'), [])->assertUnauthorized();
        $this->patchJson($this->url('/'.$image->id), ['is_primary' => true])->assertUnauthorized();
        $this->deleteJson($this->url('/'.$image->id))->assertUnauthorized();
    }

    public function test_it_uploads_a_photo_in_two_webp_sizes(): void
    {
        $file = UploadedFile::fake()->image('baju.jpg', 3000, 2000);

        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => [$file]])
            ->assertCreated()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'url', 'thumbnail_url', 'is_primary', 'sort_order']]])
            ->assertJsonPath('data.0.is_primary', true)
            ->assertJsonPath('data.0.sort_order', 0);

        $image = ProductImage::firstOrFail();
        $disk = Storage::disk('public');

        $disk->assertExists($image->path);
        $disk->assertExists($image->thumbnail_path);

        $full = getimagesizefromstring($disk->get($image->path));
        $thumbnail = getimagesizefromstring($disk->get($image->thumbnail_path));

        $this->assertSame('image/webp', $full['mime']);
        $this->assertSame(1600, $full[0]);
        $this->assertSame(480, $thumbnail[0]);
    }

    public function test_small_photos_are_not_enlarged(): void
    {
        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => [UploadedFile::fake()->image('kecil.png', 400, 300)]])
            ->assertCreated();

        $size = getimagesizefromstring(Storage::disk('public')->get(ProductImage::firstOrFail()->path));

        $this->assertSame(400, $size[0]);
    }

    public function test_only_the_first_photo_becomes_primary(): void
    {
        $first = $this->existingImage(primary: true, order: 0);

        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.jpg', 800, 600),
            ]])
            ->assertCreated()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.is_primary', false)
            ->assertJsonPath('data.0.sort_order', 1)
            ->assertJsonPath('data.1.sort_order', 2);

        $this->assertTrue($first->fresh()->is_primary);
        $this->assertSame(1, ProductImage::where('is_primary', true)->count());
    }

    public function test_it_rejects_requests_without_files(): void
    {
        $this->actingAs($this->admin)
            ->postJson($this->url(), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('images');
    }

    public function test_it_rejects_more_than_eight_files_per_upload(): void
    {
        $files = array_map(
            fn (int $i) => UploadedFile::fake()->image("f{$i}.jpg", 100, 100),
            range(1, 9),
        );

        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => $files])
            ->assertStatus(422)
            ->assertJsonValidationErrors('images');

        $this->assertSame(0, ProductImage::count());
    }

    public function test_it_rejects_oversized_files(): void
    {
        $file = UploadedFile::fake()->image('besar.jpg', 800, 600)->size(3000);

        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => [$file]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('images.0');

        $this->assertSame(0, ProductImage::count());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_it_rejects_non_image_files(): void
    {
        $file = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => [$file]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('images.0');
    }

    public function test_it_rejects_images_with_extreme_dimensions(): void
    {
        $file = UploadedFile::fake()->image('lebar.jpg', 4100, 100);

        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => [$file]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('images.0');
    }

    public function test_it_enforces_the_total_limit_per_product(): void
    {
        foreach (range(0, 8) as $order) {
            $this->existingImage(order: $order);
        }

        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => [
                UploadedFile::fake()->image('a.jpg', 100, 100),
                UploadedFile::fake()->image('b.jpg', 100, 100),
            ]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('images');

        $this->assertSame(9, ProductImage::count());

        $this->actingAs($this->admin)
            ->postJson($this->url(), ['images' => [UploadedFile::fake()->image('c.jpg', 100, 100)]])
            ->assertCreated();

        $this->assertSame(10, ProductImage::count());
    }

    public function test_admin_can_set_a_photo_as_primary(): void
    {
        $first = $this->existingImage(primary: true, order: 0);
        $second = $this->existingImage(order: 1);

        $this->actingAs($this->admin)
            ->patchJson($this->url('/'.$second->id), ['is_primary' => true])
            ->assertOk()
            ->assertJsonPath('data.is_primary', true);

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_setting_primary_requires_true(): void
    {
        $image = $this->existingImage(primary: true);

        $this->actingAs($this->admin)
            ->patchJson($this->url('/'.$image->id), ['is_primary' => false])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_primary');
    }

    public function test_admin_can_reorder_photos(): void
    {
        $a = $this->existingImage(primary: true, order: 0);
        $b = $this->existingImage(order: 1);
        $c = $this->existingImage(order: 2);

        $this->actingAs($this->admin)
            ->putJson($this->url('/order'), ['image_ids' => [$c->id, $a->id, $b->id]])
            ->assertOk()
            ->assertJsonPath('data.0.id', $c->id)
            ->assertJsonPath('data.1.id', $a->id)
            ->assertJsonPath('data.2.id', $b->id);

        $this->assertSame(0, $c->fresh()->sort_order);
        $this->assertSame(1, $a->fresh()->sort_order);
        $this->assertSame(2, $b->fresh()->sort_order);
    }

    public function test_reorder_requires_exactly_the_products_photos(): void
    {
        $a = $this->existingImage(order: 0);
        $this->existingImage(order: 1);
        $foreign = $this->existingImage(Product::factory()->create());

        $this->actingAs($this->admin)
            ->putJson($this->url('/order'), ['image_ids' => [$a->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image_ids');

        $this->actingAs($this->admin)
            ->putJson($this->url('/order'), ['image_ids' => [$a->id, $foreign->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image_ids');

        $this->actingAs($this->admin)
            ->putJson($this->url('/order'), ['image_ids' => [$a->id, $a->id]])
            ->assertStatus(422);
    }

    public function test_deleting_a_photo_removes_its_files(): void
    {
        $image = $this->existingImage(order: 0);

        $this->actingAs($this->admin)
            ->deleteJson($this->url('/'.$image->id))
            ->assertNoContent();

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
        Storage::disk('public')->assertMissing($image->thumbnail_path);
    }

    public function test_deleting_the_primary_photo_promotes_the_next_one(): void
    {
        $primary = $this->existingImage(primary: true, order: 0);
        $next = $this->existingImage(order: 1);
        $last = $this->existingImage(order: 2);

        $this->actingAs($this->admin)
            ->deleteJson($this->url('/'.$primary->id))
            ->assertNoContent();

        $this->assertTrue($next->fresh()->is_primary);
        $this->assertFalse($last->fresh()->is_primary);
    }

    public function test_photos_of_other_products_cannot_be_touched(): void
    {
        $foreign = $this->existingImage(Product::factory()->create());

        $this->actingAs($this->admin)
            ->deleteJson($this->url('/'.$foreign->id))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->patchJson($this->url('/'.$foreign->id), ['is_primary' => true])
            ->assertNotFound();

        $this->assertDatabaseHas('product_images', ['id' => $foreign->id]);
    }
}
