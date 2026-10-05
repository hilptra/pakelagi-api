<?php

namespace App\Actions\Categories;

use App\Models\Category;

class UpdateCategory
{
    public function execute(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }
}