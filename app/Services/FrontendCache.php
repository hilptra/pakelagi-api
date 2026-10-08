<?php

namespace App\Services;

use App\Jobs\RevalidateFrontendCache;
use App\Models\Event;
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

    /** Event dibuat, diubah, dihapus, atau fotonya berubah. */
    public function event(?Event $event = null): void
    {
        $tags = ['events', 'homepage'];
        if ($event) {
            $tags[] = "event:{$event->slug}";
        }
        $this->revalidate($tags);
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
