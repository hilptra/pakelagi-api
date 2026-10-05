<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Support\UniqueSlug;

class CreateCategory
{
    public function execute(array $data): Category
    {
        return Category::create([
            ...$data,
            'slug' => UniqueSlug::make(Category::class, $data['name'], 'kategori'),
        ]);
    }
}