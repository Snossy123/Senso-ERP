<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Tech Store Electronics (tenant_id = 1)
        $this->seedTechStore();

        // Fashion Hub Clothing (tenant_id = 2)
        $this->seedFashionHub();

        // Home Essentials Store (tenant_id = 3)
        $this->seedHomeEssentials();
    }

    private function seedTechStore(): void
    {
        $categories = [
            'Laptops & Computers' => ['Gaming Laptops', 'Business Laptops', 'Desktop PCs', 'Monitors'],
            'Mobile Devices' => ['Smartphones', 'Tablets', 'Accessories'],
            'Audio & Headphones' => ['Wireless Headphones', 'Earbuds', 'Speakers'],
            'Gaming' => ['Gaming Consoles', 'Gaming Peripherals', 'Games'],
        ];

        $suppliers = [
            ['name' => 'TechDistrib Inc', 'email' => 'orders@techdistrib.com', 'phone' => '555-0101', 'city' => 'New York', 'tenant_id' => 1],
            ['name' => 'Gadget Supply Co', 'email' => 'sales@gadgetsupply.com', 'phone' => '555-0102', 'city' => 'Los Angeles', 'tenant_id' => 1],
            ['name' => 'Digital Parts Ltd', 'email' => 'parts@digitalparts.com', 'phone' => '555-0103', 'city' => 'Chicago', 'tenant_id' => 1],
        ];

        $warehouses = [
            ['name' => 'Main Tech Warehouse', 'location' => '123 Tech Blvd, New York', 'manager_name' => 'John Smith', 'phone' => '555-1001', 'tenant_id' => 1],
            ['name' => 'West Coast Tech Hub', 'location' => '456 Silicon Ave, Los Angeles', 'manager_name' => 'Jane Doe', 'phone' => '555-1002', 'tenant_id' => 1],
        ];

        $this->createCategories($categories, 1);
        $this->createSuppliers($suppliers);
        $this->createWarehouses($warehouses);
        $this->createProducts('tech', 1, $suppliers, $warehouses);
    }

    private function seedFashionHub(): void
    {
        $categories = [
            'Men\'s Clothing' => ['T-Shirts', 'Jeans', 'Jackets', 'Formal Wear'],
            'Women\'s Clothing' => ['Dresses', 'Tops', 'Skirts', 'Sweaters'],
            'Footwear' => ['Sneakers', 'Boots', 'Heels', 'Sandals'],
            'Accessories' => ['Bags', 'Watches', 'Jewelry', 'Sunglasses'],
        ];

        $suppliers = [
            ['name' => 'Fashion Forward', 'email' => 'orders@fashionforward.com', 'phone' => '555-0201', 'city' => 'Miami', 'tenant_id' => 2],
            ['name' => 'Style Wholesale', 'email' => 'sales@stylewholesale.com', 'phone' => '555-0202', 'city' => 'Los Angeles', 'tenant_id' => 2],
        ];

        $warehouses = [
            ['name' => 'Fashion Central Warehouse', 'location' => '789 Fashion St, Miami', 'manager_name' => 'Sarah Wilson', 'phone' => '555-2001', 'tenant_id' => 2],
        ];

        $this->createCategories($categories, 2);
        $this->createSuppliers($suppliers);
        $this->createWarehouses($warehouses);
        $this->createProducts('fashion', 2, $suppliers, $warehouses);
    }

    private function seedHomeEssentials(): void
    {
        $categories = [
            'Kitchen' => ['Cookware', 'Appliances', 'Utensils', 'Storage'],
            'Furniture' => ['Living Room', 'Bedroom', 'Office', 'Outdoor'],
            'Home Decor' => ['Lighting', 'Wall Art', 'Rugs', 'Curtains'],
            'Bed & Bath' => ['Bedding', 'Towels', 'Mattresses', 'Pillows'],
        ];

        $suppliers = [
            ['name' => 'HomeGoods Distributors', 'email' => 'orders@homegoods.com', 'phone' => '555-0301', 'city' => 'Denver', 'tenant_id' => 3],
            ['name' => 'Living Space Supplies', 'email' => 'sales@livingspace.com', 'phone' => '555-0302', 'city' => 'Seattle', 'tenant_id' => 3],
        ];

        $warehouses = [
            ['name' => 'Home Essentials Main', 'location' => '321 Home Ave, Denver', 'manager_name' => 'Mike Johnson', 'phone' => '555-3001', 'tenant_id' => 3],
        ];

        $this->createCategories($categories, 3);
        $this->createSuppliers($suppliers);
        $this->createWarehouses($warehouses);
        $this->createProducts('home', 3, $suppliers, $warehouses);
    }

    private function createCategories(array $categories, int $tenantId): void
    {
        foreach ($categories as $parent => $children) {
            $parentModel = Category::firstOrCreate(
                ['slug' => Str::slug($parent), 'tenant_id' => $tenantId],
                [
                    'name' => $parent,
                    'is_active' => true,
                ]
            );

            foreach ($children as $child) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($child), 'tenant_id' => $tenantId],
                    [
                        'name' => $child,
                        'parent_id' => $parentModel->id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function createSuppliers(array $suppliers): void
    {
        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['email' => $supplier['email'], 'tenant_id' => $supplier['tenant_id']],
                array_merge($supplier, ['is_active' => true])
            );
        }
    }

    private function createWarehouses(array $warehouses): void
    {
        foreach ($warehouses as $warehouse) {
            Warehouse::firstOrCreate(
                ['name' => $warehouse['name'], 'tenant_id' => $warehouse['tenant_id']],
                array_merge($warehouse, ['is_active' => true])
            );
        }
    }

    private function createProducts(string $type, int $tenantId, array $suppliers, array $warehouses): void
    {
        $productsData = $this->getProductsData($type);

        foreach ($productsData as $product) {
            $category = Category::where('tenant_id', $tenantId)
                ->where('name', $product['category'])
                ->first();

            $supplier = $suppliers[array_rand($suppliers)];
            $warehouse = $warehouses[array_rand($warehouses)];

            Product::firstOrCreate(
                ['sku' => strtoupper($type) . '-' . strtoupper(substr(md5($product['name']), 0, 6)), 'tenant_id' => $tenantId],
                [
                    'name' => $product['name'],
                    'slug' => Str::slug($product['name']),
                    'description' => 'High quality ' . strtolower($product['name']) . ' for everyday use.',
                    'category_id' => $category?->id,
                    'supplier_id' => $supplier['id'] ?? null,
                    'warehouse_id' => $warehouse['id'] ?? null,
                    'purchase_price' => $product['purchase_price'],
                    'selling_price' => $product['selling_price'],
                    'stock_quantity' => rand(5, 200),
                    'min_stock_alert' => 10,
                    'is_active' => true,
                    'is_ecommerce' => true,
                ]
            );
        }
    }

    private function getProductsData(string $type): array
    {
        return match($type) {
            'tech' => [
                ['name' => 'Gaming Pro Laptop X15', 'category' => 'Gaming Laptops', 'purchase_price' => 1200, 'selling_price' => 1599],
                ['name' => 'Business Elite Laptop', 'category' => 'Business Laptops', 'purchase_price' => 800, 'selling_price' => 1099],
                ['name' => 'Desktop Workstation Z8', 'category' => 'Desktop PCs', 'purchase_price' => 1000, 'selling_price' => 1399],
                ['name' => 'UltraWide 34" Monitor', 'category' => 'Monitors', 'purchase_price' => 400, 'selling_price' => 549],
                ['name' => 'iPhone 15 Pro Max', 'category' => 'Smartphones', 'purchase_price' => 900, 'selling_price' => 1199],
                ['name' => 'Samsung Galaxy S24', 'category' => 'Smartphones', 'purchase_price' => 700, 'selling_price' => 899],
                ['name' => 'iPad Pro 12.9"', 'category' => 'Tablets', 'purchase_price' => 800, 'selling_price' => 1099],
                ['name' => 'AirPods Pro 2', 'category' => 'Wireless Headphones', 'purchase_price' => 200, 'selling_price' => 279],
                ['name' => 'Sony WH-1000XM5', 'category' => 'Wireless Headphones', 'purchase_price' => 280, 'selling_price' => 379],
                ['name' => 'JBL Flip 6 Speaker', 'category' => 'Speakers', 'purchase_price' => 100, 'selling_price' => 149],
                ['name' => 'PlayStation 5', 'category' => 'Gaming Consoles', 'purchase_price' => 400, 'selling_price' => 499],
                ['name' => 'Xbox Series X', 'category' => 'Gaming Consoles', 'purchase_price' => 450, 'selling_price' => 549],
                ['name' => 'Razer DeathAdder Mouse', 'category' => 'Gaming Peripherals', 'purchase_price' => 50, 'selling_price' => 79],
                ['name' => 'Mechanical Keyboard RGB', 'category' => 'Gaming Peripherals', 'purchase_price' => 80, 'selling_price' => 129],
                ['name' => 'Gaming Chair Pro', 'category' => 'Gaming Peripherals', 'purchase_price' => 250, 'selling_price' => 399],
            ],
            'fashion' => [
                ['name' => 'Classic Cotton T-Shirt', 'category' => 'T-Shirts', 'purchase_price' => 12, 'selling_price' => 29],
                ['name' => 'Premium Denim Jeans', 'category' => 'Jeans', 'purchase_price' => 35, 'selling_price' => 79],
                ['name' => 'Leather Biker Jacket', 'category' => 'Jackets', 'purchase_price' => 80, 'selling_price' => 159],
                ['name' => 'Slim Fit Suit', 'category' => 'Formal Wear', 'purchase_price' => 150, 'selling_price' => 299],
                ['name' => 'Floral Summer Dress', 'category' => 'Dresses', 'purchase_price' => 40, 'selling_price' => 89],
                ['name' => 'Silk Blouse', 'category' => 'Tops', 'purchase_price' => 50, 'selling_price' => 109],
                ['name' => 'Pleated Midi Skirt', 'category' => 'Skirts', 'purchase_price' => 30, 'selling_price' => 69],
                ['name' => 'Cashmere Sweater', 'category' => 'Sweaters', 'purchase_price' => 60, 'selling_price' => 139],
                ['name' => 'Running Sneakers Pro', 'category' => 'Sneakers', 'purchase_price' => 55, 'selling_price' => 119],
                ['name' => 'Ankle Boots Leather', 'category' => 'Boots', 'purchase_price' => 70, 'selling_price' => 149],
                ['name' => 'Classic Stiletto Heels', 'category' => 'Heels', 'purchase_price' => 45, 'selling_price' => 99],
                ['name' => 'Leather Tote Bag', 'category' => 'Bags', 'purchase_price' => 60, 'selling_price' => 139],
                ['name' => 'Minimalist Watch Gold', 'category' => 'Watches', 'purchase_price' => 100, 'selling_price' => 199],
                ['name' => 'Sterling Silver Necklace', 'category' => 'Jewelry', 'purchase_price' => 40, 'selling_price' => 89],
                ['name' => 'Aviator Sunglasses', 'category' => 'Sunglasses', 'purchase_price' => 25, 'selling_price' => 59],
            ],
            'home' => [
                ['name' => 'Stainless Steel Cookware Set', 'category' => 'Cookware', 'purchase_price' => 120, 'selling_price' => 249],
                ['name' => 'Instant Pot Duo', 'category' => 'Appliances', 'purchase_price' => 70, 'selling_price' => 129],
                ['name' => 'Non-Stick Frying Pan', 'category' => 'Utensils', 'purchase_price' => 25, 'selling_price' => 49],
                ['name' => 'Glass Food Storage Set', 'category' => 'Storage', 'purchase_price' => 30, 'selling_price' => 59],
                ['name' => 'Modern Sofa 3-Seater', 'category' => 'Living Room', 'purchase_price' => 500, 'selling_price' => 899],
                ['name' => 'Queen Bed Frame Wood', 'category' => 'Bedroom', 'purchase_price' => 350, 'selling_price' => 599],
                ['name' => 'Ergonomic Office Chair', 'category' => 'Office', 'purchase_price' => 180, 'selling_price' => 349],
                ['name' => 'Patio Dining Set', 'category' => 'Outdoor', 'purchase_price' => 400, 'selling_price' => 749],
                ['name' => 'LED Floor Lamp', 'category' => 'Lighting', 'purchase_price' => 45, 'selling_price' => 89],
                ['name' => 'Canvas Wall Art Set', 'category' => 'Wall Art', 'purchase_price' => 60, 'selling_price' => 119],
                ['name' => 'Persian Area Rug 5x7', 'category' => 'Rugs', 'purchase_price' => 200, 'selling_price' => 399],
                ['name' => 'Blackout Curtains Pair', 'category' => 'Curtains', 'purchase_price' => 40, 'selling_price' => 79],
                ['name' => 'Egyptian Cotton Sheet Set', 'category' => 'Bedding', 'purchase_price' => 80, 'selling_price' => 159],
                ['name' => 'Luxury Bath Towel Set', 'category' => 'Towels', 'purchase_price' => 35, 'selling_price' => 69],
                ['name' => 'Memory Foam Pillow', 'category' => 'Pillows', 'purchase_price' => 30, 'selling_price' => 59],
            ],
            default => [],
        };
    }
}
