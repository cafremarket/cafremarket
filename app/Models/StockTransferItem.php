<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTransferItem extends BaseModel
{
    use HasFactory;

    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'inventory_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
