<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics',  'description' => 'Electronic devices and accessories'],
            ['name' => 'Clothing',     'description' => 'Apparel and fashion items'],
            ['name' => 'Food & Drink', 'description' => 'Grocery and beverage items'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => $cat['description'],
                'is_active' => true,
            ]);
        }
    }
}
