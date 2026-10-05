<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/admin/categories';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Atasan',
            'measurement_fields' => ['lebar_dada', 'panjang_baju'],
        ], $overrides);
    }

    public function test_endpoints_require_authentication(): void
    {
        $category = Category::factory()->create();
        $url = self::BASE.'/'.$category->id;

        $this->getJson(self::BASE)->assertUnauthorized();
        $this->postJson(self::BASE, [])->assertUnauthorized();
        $this->putJson($url, [])->assertUnauthorized();
        $this->deleteJson($url)->assertUnauthorized();
    }

    public function test_admin_list_includes_ids_and_product_counts(): void
    {
        $atasan = Category::factory()->create(['name' => 'Atasan', 'slug' => 'atasan']);
        Category::factory()->create(['name' => 'Bawahan', 'slug' => 'bawahan']);
        Product::factory()->count(2)->create(['category_id' => $atasan->id]);

        $this->actingAs($this->admin)
            ->getJson(self::BASE)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'measurement_fields', 'products_count']]])
            ->assertJsonPath('data.0.slug', 'atasan')
            ->assertJsonPath('data.0.products_count', 2)
            ->assertJsonPath('data.1.products_count', 0);
    }

    public function test_it_creates_a_category_with_a_generated_slug(): void
    {
        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'atasan')
            ->assertJsonPath('data.measurement_fields', ['lebar_dada', 'panjang_baju'])
            ->assertJsonPath('data.products_count', 0);

        $this->assertDatabaseHas('categories', ['slug' => 'atasan', 'name' => 'Atasan']);
    }

    public function test_slug_gets_a_suffix_when_different_names_collide(): void
    {
        $this->actingAs($this->admin)->postJson(self::BASE, $this->payload(['name' => 'Atasan']))->assertCreated();

        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload(['name' => 'Atasan!']))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'atasan-2');
    }

    public function test_it_allows_a_category_without_measurement_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload(['name' => 'Aksesori', 'measurement_fields' => []]))
            ->assertCreated()
            ->assertJsonPath('data.measurement_fields', []);
    }

    public function test_create_requires_name_and_measurement_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(self::BASE, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'measurement_fields']);
    }

    public function test_create_rejects_badly_formatted_measurement_names(): void
    {
        foreach (['Lebar Dada', 'lebar-dada', '1panjang', 'LEBAR'] as $invalid) {
            $this->actingAs($this->admin)
                ->postJson(self::BASE, $this->payload(['name' => 'Tes '.$invalid, 'measurement_fields' => [$invalid]]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('measurement_fields.0');
        }
    }

    public function test_create_rejects_duplicate_and_too_many_measurement_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload(['measurement_fields' => ['lebar_dada', 'lebar_dada']]))
            ->assertStatus(422);

        $tooMany = array_map(fn (int $i) => "ukuran_{$i}", range(1, 13));

        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload(['measurement_fields' => $tooMany]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('measurement_fields');
    }

    public function test_create_rejects_a_duplicate_name(): void
    {
        Category::factory()->create(['name' => 'Atasan', 'slug' => 'atasan']);

        $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload(['name' => 'Atasan']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update_replaces_data_but_keeps_the_slug(): void
    {
        $category = Category::factory()->create([
            'name' => 'Atasan',
            'slug' => 'atasan',
            'measurement_fields' => ['lebar_dada'],
        ]);

        $this->actingAs($this->admin)
            ->putJson(self::BASE.'/'.$category->id, $this->payload([
                'name' => 'Atasan Pria',
                'measurement_fields' => ['lebar_dada', 'panjang_lengan'],
            ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Atasan Pria')
            ->assertJsonPath('data.slug', 'atasan')
            ->assertJsonPath('data.measurement_fields', ['lebar_dada', 'panjang_lengan']);
    }

    public function test_update_allows_keeping_the_same_name(): void
    {
        $category = Category::factory()->create(['name' => 'Atasan', 'slug' => 'atasan']);

        $this->actingAs($this->admin)
            ->putJson(self::BASE.'/'.$category->id, $this->payload(['name' => 'Atasan']))
            ->assertOk();
    }

    public function test_update_rejects_a_name_used_by_another_category(): void
    {
        $category = Category::factory()->create(['name' => 'Atasan', 'slug' => 'atasan']);
        Category::factory()->create(['name' => 'Bawahan', 'slug' => 'bawahan']);

        $this->actingAs($this->admin)
            ->putJson(self::BASE.'/'.$category->id, $this->payload(['name' => 'Bawahan']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_it_deletes_an_empty_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson(self::BASE.'/'.$category->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_it_refuses_to_delete_a_category_that_still_has_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->hidden()->create(['category_id' => $category->id]);
        Product::factory()->sold()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->deleteJson(self::BASE.'/'.$category->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unknown_categories_return_not_found(): void
    {
        $this->actingAs($this->admin)->putJson(self::BASE.'/9999', $this->payload())->assertNotFound();
        $this->actingAs($this->admin)->deleteJson(self::BASE.'/9999')->assertNotFound();
    }

    public function test_public_category_list_stays_minimal(): void
    {
        Category::factory()->create();

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonMissingPath('data.0.id')
            ->assertJsonMissingPath('data.0.products_count');
    }

    public function test_new_measurement_fields_are_accepted_when_saving_products(): void
    {
        $categoryId = $this->actingAs($this->admin)
            ->postJson(self::BASE, $this->payload(['name' => 'Topi', 'measurement_fields' => ['diameter']]))
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products', [
                'category_id' => $categoryId,
                'name' => 'Topi Baseball',
                'price' => 45000,
                'size_label' => 'All size',
                'condition' => 'good',
                'measurements' => ['diameter' => 20],
            ])
            ->assertCreated();
    }
}