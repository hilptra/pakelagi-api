<?php

namespace Tests\Feature\Services;

use App\Jobs\RevalidateFrontendCache;
use App\Models\Product;
use App\Services\FrontendCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class FrontendCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    private function configure(): void
    {
        config([
            'pakelagi.frontend.revalidate_url' => 'https://frontend.test/api/revalidate',
            'pakelagi.frontend.revalidate_secret' => 'rahasia',
        ]);
    }

    public function test_product_changes_revalidate_the_catalog_and_the_product_page(): void
    {
        $this->configure();
        $product = Product::factory()->create(['slug' => 'kemeja']);

        app(FrontendCache::class)->product($product);

        Bus::assertDispatched(
            RevalidateFrontendCache::class,
            fn (RevalidateFrontendCache $job) => $job->tags === ['catalog', 'product:kemeja'],
        );
    }

    public function test_category_changes_revalidate_categories_and_the_catalog(): void
    {
        $this->configure();

        app(FrontendCache::class)->categories();

        Bus::assertDispatched(
            RevalidateFrontendCache::class,
            fn (RevalidateFrontendCache $job) => $job->tags === ['categories', 'catalog'],
        );
    }

    public function test_nothing_is_dispatched_without_configuration(): void
    {
        $cache = app(FrontendCache::class);

        $cache->product(Product::factory()->create());
        $cache->categories();

        Bus::assertNothingDispatched();
    }

    public function test_nothing_is_dispatched_without_the_secret(): void
    {
        config(['pakelagi.frontend.revalidate_url' => 'https://frontend.test/api/revalidate']);

        app(FrontendCache::class)->categories();

        Bus::assertNothingDispatched();
    }
}