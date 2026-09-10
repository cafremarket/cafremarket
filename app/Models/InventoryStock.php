<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryStock extends BaseModel
{
    use HasFactory;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'shop_id',
        'inventory_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'damaged_quantity',
        'reorder_level',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'reorder_level' => 'integer',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function availableQuantity(): int
    {
        return max(0, (int) $this->quantity - (int) $this->reserved_quantity);
    }

    public function isLowStock(): bool
    {
        $level = $this->reorder_level;
        if ($level === null) {
            $level = config('shop_settings.alert_quantity') ?? 0;
        }

        return $this->availableQuantity() <= (int) $level;
    }

    public function scopeLowStock($query)
    {
        $shopAlert = (int) (config('shop_settings.alert_quantity') ?? 0);

        return $query->whereRaw(
            '(quantity - reserved_quantity) <= COALESCE(reorder_level, ?)',
            [$shopAlert]
        );
    }
}
