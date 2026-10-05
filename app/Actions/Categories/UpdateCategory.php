<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Services\FrontendCache;

class UpdateCategory
{
    public function __construct(private readonly FrontendCache $frontend) {}

    public function execute(Category $category, array $data): Category
    {
        $category->update($data);

        $this->frontend->categories();

        return $category;
    }
}