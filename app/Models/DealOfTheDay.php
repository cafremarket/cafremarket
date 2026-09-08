<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealOfTheDay extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'deal_of_the_day';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'deal_date',
        'inventory_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'deal_date' => 'date',
    ];

    /**
     * Inventory listing assigned to this deal day.
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
