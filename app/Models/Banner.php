<?php

namespace App\Models;

use App\Common\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Banner extends BaseModel
{
    use HasFactory, Imageable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'banners';

    /**
     * The attributes that should be casted to boolean types.
     *
     * @var array
     */
    public const CHANNEL_WEB = 'web';

    public const CHANNEL_APP = 'app';

    public const TYPE_SINGLE = 'single';

    public const TYPE_SLIDER = 'slider';

    public const TYPE_COLOUR = 'colour';

    public const LAYOUT_FULL = 12;

    public const LAYOUT_THIRD = 4;

    protected $casts = [
        'effect' => 'boolean',
        'hide_text' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'link',
        'link_label',
        'bg_color',
        'group_id',
        'columns',
        'shop_id',
        'channel',
        'order',
        'effect',
        'hide_text',
        'display_type',
    ];

    public function scopeForWeb($query)
    {
        return $query->where(function ($q) {
            $q->where('channel', self::CHANNEL_WEB)->orWhereNull('channel');
        });
    }

    public function scopeForApp($query)
    {
        return $query->where('channel', self::CHANNEL_APP);
    }

    /**
     * Get the group for the banner.
     */
    public function group()
    {
        return $this->belongsTo(BannerGroup::class);
    }

    /**
     * Setters
     */
    public function setOrderAttribute($value)
    {
        $this->attributes['order'] = $value ?? 100;
    }

    // public function setOptionsAttribute($value)
    // {
    //     $this->attributes['options'] = serialize($value);
    // }

    // /**
    //  * Getters
    //  */
    // public function getOptionsAttribute($value)
    // {
    //     return unserialize($value);
    // }
}
