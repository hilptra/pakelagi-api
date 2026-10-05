<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Services\FrontendCache;
use App\Support\UniqueSlug;

class CreateCategory
{
    public function __construct(private readonly FrontendCache $frontend) {}

    public function execute(array $data): Category
    {
        $category = Category::create([
            ...$data,
            'slug' => UniqueSlug::make(Category::class, $data['name'], 'kategori'),
        ]);

        $this->frontend->categories();

        return $category;
    }
}