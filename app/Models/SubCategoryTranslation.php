<?php

namespace App\Models;

class SubCategoryTranslation extends TranslationModel
{
    protected $table = 'translation_sub_categories';

    protected $fillable = [
        'sub_category_id',
        'slug',
        'lang',
        'translation',
    ];

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }
}
