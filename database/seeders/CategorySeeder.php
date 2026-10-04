<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Atasan', 'slug' => 'atasan', 'measurement_fields' => ['lebar_dada', 'panjang_baju', 'panjang_lengan']],
            ['name' => 'Bawahan', 'slug' => 'bawahan', 'measurement_fields' => ['lingkar_pinggang', 'panjang', 'lebar_paha']],
            ['name' => 'Outer', 'slug' => 'outer', 'measurement_fields' => ['lebar_dada', 'panjang', 'panjang_lengan', 'lebar_bahu']],
            ['name' => 'Aksesori', 'slug' => 'aksesori', 'measurement_fields' => []],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
