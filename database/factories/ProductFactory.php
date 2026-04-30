<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /** @var array<string> */
    private static array $products = [
        'Laptop Pro 15"', 'Wireless Mouse', 'Mechanical Keyboard', 'USB-C Hub',
        'Monitor 27" 4K', 'Webcam HD 1080p', 'Noise-Cancelling Headphones',
        'External SSD 1TB', 'Standing Desk Mat', 'Ergonomic Chair',
        'LED Desk Lamp', 'Cable Management Kit', 'Thunderbolt Dock',
        'Graphics Tablet', 'Portable Charger 20000mAh',
    ];

    public function definition(): array
    {
        return [
            'product_name' => fake()->unique()->randomElement(self::$products),
            'description' => fake()->sentence(),
            'unit_price' => fake()->randomFloat(2, 5, 2000),
            'stock_quantity' => fake()->numberBetween(10, 200),
        ];
    }
}
