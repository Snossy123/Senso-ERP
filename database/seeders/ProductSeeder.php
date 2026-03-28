<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $electronics = Category::where('name', 'Electronics')->first();
        $clothing    = Category::where('name', 'Clothing')->first();
        $food        = Category::where('name', 'Food & Drink')->first();
        $supplier1   = Supplier::first();
        $supplier2   = Supplier::skip(1)->first();
        $warehouse   = Warehouse::first();

        $products = [
            // Ecommerce enabled (5)
            ['name' => 'Wireless Bluetooth Headphones', 'sku' => 'ELEC-001', 'category' => $electronics, 'purchase_price' => 45.00,  'selling_price' => 89.99,  'stock_quantity' => 50, 'is_ecommerce' => true,  'supplier' => $supplier1],
            ['name' => 'USB-C Charging Cable 2m',       'sku' => 'ELEC-002', 'category' => $electronics, 'purchase_price' => 5.00,   'selling_price' => 14.99,  'stock_quantity' => 200,'is_ecommerce' => true,  'supplier' => $supplier1],
            ['name' => 'Classic White T-Shirt',         'sku' => 'CLO-001',  'category' => $clothing,    'purchase_price' => 8.00,   'selling_price' => 24.99,  'stock_quantity' => 100,'is_ecommerce' => true,  'supplier' => $supplier2],
            ['name' => 'Running Sneakers Blue',         'sku' => 'CLO-002',  'category' => $clothing,    'purchase_price' => 35.00,  'selling_price' => 79.99,  'stock_quantity' => 40, 'is_ecommerce' => true,  'supplier' => $supplier2],
            ['name' => 'Organic Green Tea 250g',        'sku' => 'FOOD-001', 'category' => $food,        'purchase_price' => 6.00,   'selling_price' => 12.99,  'stock_quantity' => 150,'is_ecommerce' => true,  'supplier' => $supplier2],
            // POS / inventory only (5)
            ['name' => 'Wireless Mouse',                'sku' => 'ELEC-003', 'category' => $electronics, 'purchase_price' => 12.00,  'selling_price' => 29.99,  'stock_quantity' => 30, 'is_ecommerce' => false, 'supplier' => $supplier1],
            ['name' => 'Mechanical Keyboard',           'sku' => 'ELEC-004', 'category' => $electronics, 'purchase_price' => 55.00,  'selling_price' => 119.99, 'stock_quantity' => 15, 'is_ecommerce' => false, 'supplier' => $supplier1],
            ['name' => 'Denim Jacket',                  'sku' => 'CLO-003',  'category' => $clothing,    'purchase_price' => 30.00,  'selling_price' => 69.99,  'stock_quantity' => 25, 'is_ecommerce' => false, 'supplier' => $supplier2],
            ['name' => 'Instant Coffee 500g',           'sku' => 'FOOD-002', 'category' => $food,        'purchase_price' => 8.00,   'selling_price' => 15.99,  'stock_quantity' => 3,  'is_ecommerce' => false, 'supplier' => $supplier2],
            ['name' => 'Filtered Water Bottle 1L',      'sku' => 'FOOD-003', 'category' => $food,        'purchase_price' => 3.00,   'selling_price' => 7.99,   'stock_quantity' => 80, 'is_ecommerce' => false, 'supplier' => $supplier2],
        ];

        foreach ($products as $p) {
            Product::create([
                'sku'            => $p['sku'],
                'name'           => $p['name'],
                'slug'           => Str::slug($p['name']),
                'category_id'    => $p['category']?->id,
                'supplier_id'    => $p['supplier']?->id,
                'warehouse_id'   => $warehouse?->id,
                'purchase_price' => $p['purchase_price'],
                'selling_price'  => $p['selling_price'],
                'stock_quantity' => $p['stock_quantity'],
                'min_stock_alert'=> 5,
                'unit'           => 'piece',
                'is_active'      => true,
                'is_ecommerce'   => $p['is_ecommerce'],
            ]);
        }
    }
}
