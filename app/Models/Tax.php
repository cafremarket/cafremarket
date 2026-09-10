<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends BaseModel
{
    use HasFactory, SoftDeletes;

    const DEFAULT_TAX_ID = 1;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'taxes';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'deleted_at'];

    /**
     * Get the Country associated with the tax.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the State associated with the tax.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the Shop associated with the tax.
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Catalog products that use this tax.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tax')->withTimestamps();
    }

    /**
     * Get the carts for the tax.
     */
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the orders for the tax.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isFixed(): bool
    {
        return strtolower((string) ($this->type ?? self::TYPE_PERCENT)) === self::TYPE_FIXED;
    }

    public function isPercent(): bool
    {
        return ! $this->isFixed();
    }

    /**
     * Get the rate formated in readable text.
     *
     * @return string
     */
    public function getLabelAttribute()
    {
        if ($this->isFixed()) {
            return get_formated_currency($this->taxrate, config('system_settings.decimals', 2));
        }

        return get_formated_decimal($this->taxrate, true, 2).'%';
    }

    /**
     * Create new state and set the id if the given value is not available
     */
    public function setStateIdAttribute($value)
    {
        if (! is_numeric($value) and $value != null) {
            $state = State::create(['name' => $value, 'country_id' => \Request::input('country_id')]);
            $value = $state->id;
        }

        $this->attributes['state_id'] = $value;
    }
}
