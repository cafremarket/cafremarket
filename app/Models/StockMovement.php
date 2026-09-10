<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMovement extends BaseModel
{
    use HasFactory;

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUST = 'adjust';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_SALE = 'sale';
    public const TYPE_REFUND = 'refund';
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_RESERVE = 'reserve';
    public const TYPE_RELEASE = 'release';

    protected $table = 'stock_movements';

    protected $fillable = [
        'shop_id',
        'inventory_id',
        'warehouse_id',
        'user_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
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

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
