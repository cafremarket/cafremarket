<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The customer's overall feedback on a single order. Strictly one per order
 * (order_id is unique), written once the order has been delivered.
 */
class OrderFeedback extends Model
{
    protected $table = 'order_feedbacks';

    protected $fillable = [
        'order_id',
        'shop_id',
        'customer_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class)->withDefault();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }
}
