<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

use App\Traits\Loggable;

class Product extends Model
{
    use SoftDeletes, BelongsToTenant, Loggable;

    protected $fillable = [
        'tenant_id', 'sku', 'name', 'slug', 'description', 'category_id', 'supplier_id', 'warehouse_id',
        'purchase_price', 'selling_price', 'stock_quantity', 'min_stock_alert',
        'weight', 'unit', 'unit_id', 'barcode', 'image', 'is_active', 'is_ecommerce',
        'has_variants', 'valuation_method'
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'is_ecommerce'   => 'boolean',
        'has_variants'   => 'boolean',
        'purchase_price' => 'decimal:2',
        'selling_price'  => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_alert;
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : asset('assets/images/placeholder-product.png');
    }
}
