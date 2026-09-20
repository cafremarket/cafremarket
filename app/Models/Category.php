<?php

namespace App\Models;

use App\Common\CascadeSoftDeletes;
use App\Common\Imageable;
use App\Common\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Top-level, admin-managed category. Stores can only select from these
 * (and their SubCategory children) — they can never create, edit, or
 * delete a Category.
 */
class Category extends BaseModel
{
    use CascadeSoftDeletes, HasFactory, Imageable, SoftDeletes, Translatable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'active',
        'featured',
        'meta_title',
        'meta_description',
    ];

    /**
     * Cascade Soft Deletes Relationships
     *
     * @var array
     */
    protected $cascadeDeletes = ['subCategories'];

    /**
     * The boot method for the Category model.
     *
     * This method is called when the Category model is being booted.
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
     * Get the SubCategories under this Category.
     */
    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id')->orderBy('name', 'asc');
    }

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class);
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
        return ['admin.catalog.category.edit'];
    }
}
