<?php

namespace App\Models;

class CategoryTranslation extends TranslationModel
{
    protected $table = 'translation_categories';

    protected $fillable = [
        'category_id',
        'lang',
        'translation',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
