<?php

return [
    'modules' => [
        'pos' => [
            'feature_key' => 'pos',
            'icon' => 'fe-shopping-cart',
            'limits' => [
                'users' => ['type' => 'number', 'label' => 'users', 'default' => 5],
                'devices' => ['type' => 'number', 'label' => 'devices', 'default' => 3],
            ],
        ],
        'ecommerce' => [
            'feature_key' => 'inventory',
            'icon' => 'fe-shopping-bag',
            'limits' => [
                'products' => ['type' => 'number', 'label' => 'products', 'default' => 500],
                'orders_per_month' => ['type' => 'number', 'label' => 'orders_per_month', 'default' => 1000],
                'domains' => ['type' => 'number', 'label' => 'domains', 'default' => 1],
                'warehouses' => ['type' => 'boolean', 'label' => 'warehouses', 'default' => false],
                'discount_coupons' => ['type' => 'boolean', 'label' => 'discount_coupons', 'default' => false],
            ],
        ],
        'accounting' => [
            'feature_key' => 'reports',
            'icon' => 'fe-book',
            'limits' => [
                'journal_entries_per_month' => ['type' => 'number', 'label' => 'journal_entries', 'default' => 500],
            ],
        ],
        'inventory' => [
            'feature_key' => 'inventory',
            'icon' => 'fe-package',
            'limits' => [
                'products' => ['type' => 'number', 'label' => 'products', 'default' => 1000],
                'warehouses' => ['type' => 'number', 'label' => 'warehouses', 'default' => 3],
            ],
        ],
        'crm' => [
            'feature_key' => 'customers',
            'icon' => 'fe-users',
            'limits' => [
                'customers' => ['type' => 'number', 'label' => 'customers', 'default' => 500],
            ],
        ],
        'reports' => [
            'feature_key' => 'reports',
            'icon' => 'fe-bar-chart-2',
            'limits' => [
                'export_per_month' => ['type' => 'number', 'label' => 'exports', 'default' => 50],
            ],
        ],
        'api' => [
            'feature_key' => 'api',
            'icon' => 'fe-code',
            'limits' => [
                'requests_per_day' => ['type' => 'number', 'label' => 'api_requests', 'default' => 10000],
            ],
        ],
        'procurement' => [
            'feature_key' => 'suppliers',
            'icon' => 'fe-truck',
            'limits' => [
                'suppliers' => ['type' => 'number', 'label' => 'suppliers', 'default' => 50],
            ],
        ],
    ],
];
