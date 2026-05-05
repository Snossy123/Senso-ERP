<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'tenant_id' => 1,
            'sku' => strtoupper($this->faker->unique()->bothify('PRO-????-####')),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'purchase_price' => $this->faker->randomFloat(2, 10, 500),
            'selling_price' => $this->faker->randomFloat(2, 500, 1000),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'min_stock_alert' => 5,
            'is_active' => true,
            'is_ecommerce' => true,
            'has_variants' => false,
            'unit' => 'pcs',
        ];
    }
}
