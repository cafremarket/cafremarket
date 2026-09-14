<?php

namespace App\Models;

use App\Common\Addressable;
use App\Common\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends BaseModel
{
    use Addressable, HasFactory, Imageable, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'warehouses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id',
        'name',
        'email',
        'incharge',
        'description',
        'opening_time',
        'close_time',
        'business_days',
        'pickup_instruction',
        'active',
    ];

    /**
     * Get the country for the warehouse.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the manager of the warehouse.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'incharge')->withDefault();
    }

    /**
     * Get the Users associated with the warehouse.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** get warehouse address*/
    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    /**
     * The address to show for pickup. Most shops never set a separate
     * warehouse address (the default warehouse created for every shop is
     * unaddressed), so fall back to the shop's own registered address
     * rather than showing pickup details as blank.
     */
    public function pickupAddress(): ?Address
    {
        return $this->address ?: optional($this->shop)->storeAddress();
    }

    /**
     * Get staff list for the user.
     *
     * @return array
     */
    public function getUserListAttribute()
    {
        return $this->users->pluck('id')->toArray();
    }

    /**
     * Get the Shop associated with the blog post.
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Listings that still point at this warehouse as primary.
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Per-SKU stock rows stored in this warehouse.
     */
    public function stocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Inventories that have stock in this warehouse (via stock table).
     */
    public function stockedInventories()
    {
        return $this->belongsToMany(Inventory::class, 'inventory_stocks')
            ->withPivot(['quantity', 'reserved_quantity', 'damaged_quantity', 'reorder_level'])
            ->withTimestamps();
    }

    /**
     * Get all of the products for the warehouse.
     */
    public function products()
    {
        return $this->hasManyThrough(Product::class, Inventory::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function totalOnHandQuantity(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }

    /**
     * Set business_days as serialized data for the model.
     */
    public function setBusinessDaysAttribute($business_days)
    {
        $this->attributes['business_days'] = serialize($business_days);
    }

    /**
     * Get business_days for the model.
     *
     * @return array
     */
    public function getBusinessDaysAttribute($business_days)
    {
        // check if business days is a comma separated string if it is return it as an array
        // This is for ensuring compatibility with older versions.
        if (is_string($business_days) && strpos($business_days, ',') !== false) {
            return explode(',', $business_days);
        }

        if (empty($business_days)) {
            return null;
        }

        // unserialize() returns false (not null) on empty/invalid input — never
        // hand that back as "business_days", it breaks any consumer expecting
        // either an array or null (e.g. the mobile apps' JSON parsers).
        $unserialized = @unserialize($business_days);

        return is_array($unserialized) ? $unserialized : null;
    }
}
