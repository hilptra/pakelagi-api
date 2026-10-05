<?php

namespace App\Actions\Categories;

use App\Models\Category;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function execute(Category $category): void
    {
        $count = $category->products()->count();

        if ($count > 0) {
            throw ValidationException::withMessages([
                'category' => ["Kategori masih memiliki {$count} produk dan tidak bisa dihapus."],
            ]);
        }

        $category->delete();
    }
}