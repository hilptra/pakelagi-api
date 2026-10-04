<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Category::all()->each(function (Category $category) {
            Product::factory()->count(6)->forCategory($category)->create();
            Product::factory()->count(2)->forCategory($category)->sold()->create();
            Product::factory()->count(1)->forCategory($category)->hidden()->create();
        });
    }
}