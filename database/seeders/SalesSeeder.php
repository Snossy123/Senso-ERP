<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTechStore();
        $this->seedFashionHub();
        $this->seedHomeEssentials();
    }

    private function seedTechStore(): void
    {
        $tenantId = 1;
        $this->createCustomers($tenantId, 'tech');
        $this->createSales($tenantId);
        $this->createOrders($tenantId);
        $this->createActivities($tenantId);
    }

    private function seedFashionHub(): void
    {
        $tenantId = 2;
        $this->createCustomers($tenantId, 'fashion');
        $this->createSales($tenantId);
        $this->createOrders($tenantId);
        $this->createActivities($tenantId);
    }

    private function seedHomeEssentials(): void
    {
        $tenantId = 3;
        $this->createCustomers($tenantId, 'home');
        $this->createSales($tenantId);
        $this->createOrders($tenantId);
        $this->createActivities($tenantId);
    }

    private function createCustomers(int $tenantId, string $type): void
    {
        $firstNames = ['John', 'Emma', 'Michael', 'Sophia', 'William', 'Olivia', 'James', 'Ava', 'Robert', 'Isabella'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose'];

        for ($i = 0; $i < 15; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = strtolower($firstName) . '.' . strtolower($lastName) . ($i + 1) . '@' . $type . 'customer.com';

            Customer::firstOrCreate(
                ['email' => $email, 'tenant_id' => $tenantId],
                [
                    'name' => "$firstName $lastName",
                    'phone' => '555-' . rand(1000, 9999),
                    'address' => rand(100, 9999) . ' ' . ucfirst($type) . ' Street',
                    'city' => $cities[array_rand($cities)],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );
        }
    }

    private function createSales(int $tenantId): void
    {
        $products = Product::where('tenant_id', $tenantId)->get();
        $users = User::where('tenant_id', $tenantId)->get();
        $taxRate = 0.08;

        if ($products->isEmpty() || $users->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 25; $i++) {
            $user = $users->random();
            $items = $products->random(rand(1, 4));
            $subtotal = 0;
            $saleItems = [];

            foreach ($items as $product) {
                $quantity = rand(1, 3);
                $discount = rand(0, 10) > 7 ? rand(5, 15) : 0;
                $unitPrice = $product->selling_price;
                $itemTotal = ($unitPrice * $quantity) - $discount;
                $subtotal += $itemTotal;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'total' => $itemTotal,
                ];
            }

            $taxAmount = $subtotal * $taxRate;
            $total = $subtotal + $taxAmount;

            $paymentMethods = ['cash', 'card', 'bank_transfer'];
            $paymentStatuses = ['paid', 'paid', 'paid', 'partial'];

            $saleNumber = 'SL-' . now()->format('Ymd') . '-' . strtoupper(substr(md5($tenantId . $i . time()), 0, 6));

            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                'notes' => rand(0, 10) > 7 ? 'Customer requested gift wrapping' : null,
                'tenant_id' => $tenantId,
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            foreach ($saleItems as $item) {
                SaleItem::create(array_merge($item, [
                    'sale_id' => $sale->id,
                    'tenant_id' => $tenantId,
                ]));
            }
        }
    }

    private function createOrders(int $tenantId): void
    {
        $products = Product::where('tenant_id', $tenantId)->where('is_ecommerce', true)->get();
        $taxRate = 0.08;

        if ($products->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'delivered', 'delivered'];
        $paymentStatuses = ['pending', 'paid', 'paid', 'paid'];
        $paymentMethods = ['cash_on_delivery', 'online'];
        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix'];

        for ($i = 0; $i < 20; $i++) {
            $items = $products->random(rand(1, 3));
            $subtotal = 0;
            $orderItems = [];
            $customerName = 'Guest Customer';

            foreach ($items as $product) {
                $quantity = rand(1, 2);
                $unitPrice = $product->selling_price;
                $itemTotal = $unitPrice * $quantity;
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                ];
            }

            $shippingCost = rand(0, 1) > 0 ? 9.99 : 0;
            $taxAmount = $subtotal * $taxRate;
            $total = $subtotal + $shippingCost + $taxAmount;

            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(substr(md5($tenantId . $i . uniqid()), 0, 6));

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_name' => $customerName . ' ' . ($i + 1),
                'customer_email' => 'order' . ($i + 1) . '@example.com',
                'customer_phone' => '555-' . rand(1000, 9999),
                'shipping_address' => rand(100, 9999) . ' Main Street',
                'city' => $cities[array_rand($cities)],
                'status' => $statuses[array_rand($statuses)],
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                'notes' => rand(0, 10) > 8 ? 'Please leave at door' : null,
                'tenant_id' => $tenantId,
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create(array_merge($item, [
                    'order_id' => $order->id,
                    'tenant_id' => $tenantId,
                ]));
            }
        }
    }

    private function createActivities(int $tenantId): void
    {
        $users = User::where('tenant_id', $tenantId)->get();

        if ($users->isEmpty()) {
            return;
        }

        $actions = [
            ['type' => 'auth', 'action' => 'login', 'description' => 'Logged in'],
            ['type' => 'crud', 'action' => 'create', 'description' => 'Created new product'],
            ['type' => 'crud', 'action' => 'update', 'description' => 'Updated inventory'],
            ['type' => 'sale', 'action' => 'create', 'description' => 'Processed sale'],
            ['type' => 'order', 'action' => 'create', 'description' => 'New order received'],
        ];

        for ($i = 0; $i < 30; $i++) {
            $user = $users->random();
            $action = $actions[array_rand($actions)];

            Activity::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'type' => $action['type'],
                'action' => $action['action'],
                'description' => $user->name . ' ' . strtolower($action['description']),
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subDays(rand(0, 14))->subHours(rand(0, 23)),
            ]);
        }
    }
}
