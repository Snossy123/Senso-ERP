# Senso-ERP System Architecture & Context

## Overview
Senso-ERP is a multi-tenant, SaaS-based Enterprise Resource Planning (ERP) application built with Laravel (PHP 8.2+). It serves as a comprehensive system for businesses to manage their inventory, accounting, sales (POS), and operations. 

## Core Capabilities & Modules

### 1. Multi-Tenancy & SaaS Management
- **Models**: `Tenant`, `Plan`, `UsageTracking`, `Setting`, `Activity`
- **Logic**: Subscriptions, trials, billing cycles, and feature/capacity limits (e.g., max users, max products, max orders) are managed per tenant.
- **Tenancy Architecture**: Data is segmented by `tenant_id` on the core transaction and master data tables.

### 2. Accounting Module
- **Models**: `Account`, `JournalEntry`, `JournalEntryLine`, `FinancialPeriod`, `AccountSetting`
- **Logic**: Handles double-entry bookkeeping. Automatically logs journal entries for sales, purchases, and other financial movements across standard accounting principles. Operational flows (POS, Inventory, etc.) are mapped to specific GL accounts via `AccountSetting`.

### 3. Inventory & Warehouse Management
- **Models**: `Product`, `ProductVariant`, `ProductWarehouseStock`, `Category`, `Unit`, `Warehouse`, `StockMovement`, `StockTransfer`
- **Logic**: Supports multi-warehouse operations. Tracks stock quantities by product and variants. Records historical stock movements and facilitates stock transfers between warehouses.

### 4. Sales & Point of Sale (POS)
- **Models**: `Sale`, `SaleItem`, `SaleRefund`, `PosShift`, `HeldOrder`
- **Logic**: Integrates directly with inventory to deduct stock. Handles shift-based POS operations, permitting cashiers to open/close shifts and hold/resume orders.

### 5. Procurement / Purchasing
- **Models**: `PurchaseOrder`, `PurchaseOrderItem`, `Supplier`
- **Logic**: Facilitates ordering from suppliers, directly increasing warehouse stock upon order fulfillment.

### 6. User Management & Authorization
- **Models**: `User`, `Role`, `Permission`
- **Logic**: Granular role-based access control (RBAC). A user belongs to a tenant and has specific permissions for allowed actions.

## Technology Stack
- **Backend Framework**: Laravel 12 (PHP 8.2+)
- **Frontend Stack**: View templates are powered by Laravel Blade with components covering graphs (ChartJS, Echarts, Morris), Modals, SweetAlerts, and responsive grids. Uses SCSS/Vue capabilities via Laravel Vite/Mix.
- **Reporting Engine**: Excel Exports (`maatwebsite/excel`), PDF Generation (`barryvdh/laravel-dompdf`).

## Recent Expansions (Contextual)
- **ETA Integration**: compliance rules for e-invoicing.
- **Paymob**: Payment gateway integration.

## Ongoing Work
This system is an actively evolving platform. Current goals typically revolve around solidifying module logic (such as strict zero-stock prevention, accounting tie-ins, API payload mapping for ETA) and refining the frontend UX to be robust, modern, and user-friendly.
