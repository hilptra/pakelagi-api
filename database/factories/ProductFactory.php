<?php

namespace Database\Factories;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Kemeja Flanel', 'Kaos Oversize', 'Kemeja Denim', 'Hoodie',
            'Sweater Rajut', 'Jaket Bomber', 'Celana Chino', 'Jeans Straight',
            'Rok Plisket', 'Blazer', 'Kemeja Batik', 'Cardigan',
        ]).' '.fake()->randomElement([
            'Hitam', 'Putih', 'Navy', 'Abu-abu', 'Cokelat', 'Hijau Olive', 'Krem',
        ]);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(25, 400) * 1000,
            'brand' => fake()->optional()->randomElement(['Uniqlo', 'H&M', 'Zara', 'Nike', 'Adidas']),
            'size_label' => fake()->randomElement(['S', 'M', 'L', 'XL', 'All size']),
            'condition' => fake()->randomElement(ProductCondition::cases()),
            'condition_notes' => fake()->optional(0.3)->sentence(),
            'measurements' => null,
            'status' => ProductStatus::Available,
            'sold_at' => null,
        ];
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn () => [
            'category_id' => $category->id,
            'measurements' => collect($category->measurement_fields)
                ->mapWithKeys(fn (string $field) => [$field => fake()->numberBetween(30, 100)])
                ->all() ?: null,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn () => [
            'status' => ProductStatus::Sold,
            'sold_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Hidden]);
    }
}
