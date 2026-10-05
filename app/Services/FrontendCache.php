<?php

namespace App\Services;

use App\Jobs\RevalidateFrontendCache;
use App\Models\Product;

class FrontendCache
{
    /** Produk dibuat, diubah, dihapus, berganti status, atau fotonya berubah. */
    public function product(Product $product): void
    {
        $this->revalidate(['catalog', "product:{$product->slug}"]);
    }

    /** Kategori dibuat, diubah, atau dihapus. */
    public function categories(): void
    {
        $this->revalidate(['categories', 'catalog']);
    }

    /**
     * @param  array<int, string>  $tags
     */
    private function revalidate(array $tags): void
    {
        if (! config('pakelagi.frontend.revalidate_url') || ! config('pakelagi.frontend.revalidate_secret')) {
            return;
        }

        RevalidateFrontendCache::dispatch($tags);
    }
}
