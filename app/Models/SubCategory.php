<?php

namespace App\Models;

use App\Common\Imageable;
use App\Common\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * Child of a top-level Category. This is the level products actually
 * attach to (was the old leaf "Category" before the 2-level redesign) —
 * admin-managed only, stores select from the published list.
 */
class SubCategory extends BaseModel
{
    use HasFactory, Imageable, Searchable, SoftDeletes, Translatable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'sub_categories';

    /**
     * The attributes that should be mutated to dates. (as carbon instances)
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'category_id',
        'slug',
        'description',
        'active',
        'featured',
        'meta_title',
        'meta_description',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $searchable = [];
        $searchable['id'] = (string) $this->id;
        $searchable['name'] = $this->name;
        $searchable['slug'] = $this->slug;
        $searchable['active'] = (bool) $this->active;
        $searchable['created_at'] = $this->created_at->timestamp;

        return $searchable;
    }

    /**
     * The boot method for the SubCategory model.
     *
     * This method is called when the SubCategory model is being booted.
     * It adds a global scope to the model to include translations based on the current locale.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('withTranslations', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                $query->where('lang', app()->getLocale())->whereNotNull('translation');
            }]);
        });
    }

    /**
     * Get all listings for the sub-category.
     */
    public function listings()
    {
        return $this->belongsToMany(Inventory::class, 'category_product', 'category_id', 'product_id', null, 'product_id')
            ->whereNull('inventories.parent_id')
            ->groupBy('inventories.product_id', 'inventories.shop_id');
    }

    /**
     * Get the parent Category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id')->withTrashed();
    }

    /**
     * Get the products for the sub-category.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }

    /**
     * Get the attributes of respective sub-categories.
     */
    public function attrsList(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_categories', 'category_id', 'attribute_id')
            ->orderBy('name', 'asc')->withTimestamps();
    }

    public function translations()
    {
        return $this->hasMany(SubCategoryTranslation::class);
    }

    /**
     * Setters
     */
    public function setFeaturedAttribute($value)
    {
        $this->attributes['featured'] = (bool) $value;
    }

    public function getNameAttribute($value)
    {
        return $this->translateAttribute('name') ?? $value;
    }

    public function getDescriptionAttribute($value)
    {
        return $this->translateAttribute('description') ?? $value;
    }

    /**
     * Scope a query to only include Featured records.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', 1);
    }

    protected function getTranslationDisabledRoutes()
    {
        return ['admin.catalog.subcategory.edit'];
    }
}
