<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Services\FrontendCache;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function __construct(private readonly FrontendCache $frontend) {}

    public function execute(Category $category): void
    {
        $count = $category->products()->count();

        if ($count > 0) {
            throw ValidationException::withMessages([
                'category' => ["Kategori masih memiliki {$count} produk dan tidak bisa dihapus."],
            ]);
        }

        $category->delete();

        $this->frontend->categories();
    }
}
