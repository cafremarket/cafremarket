<?php

namespace App\Http\Requests\Concerns;

use App\Models\SubCategory;
use Illuminate\Validation\Validator;

/**
 * Every product must have a parent Category and at least one SubCategory
 * that belongs to that Category.
 */
trait ValidatesProductCategories
{
    protected function prepareProductCategories(): void
    {
        $list = $this->input('category_list');

        if ($list === null || $list === '') {
            $list = [];
        } elseif (! is_array($list)) {
            $list = [$list];
        }

        $list = array_values(array_filter($list, function ($id) {
            return $id !== null && $id !== '';
        }));

        $this->merge(['category_list' => $list]);

        if (! $this->filled('parent_category_id') && ! empty($list)) {
            $parent = SubCategory::where('id', $list[0])->value('category_id');
            if ($parent) {
                $this->merge(['parent_category_id' => $parent]);
            }
        }
    }

    protected function productCategoryRules(): array
    {
        return [
            'parent_category_id' => 'required|integer|exists:categories,id',
            'category_list' => 'required|array|min:1',
            'category_list.*' => 'required|integer|exists:sub_categories,id',
        ];
    }

    protected function productCategoryMessages(): array
    {
        return [
            'parent_category_id.required' => trans('validation.parent_category_required'),
            'parent_category_id.exists' => trans('validation.parent_category_required'),
            'category_list.required' => trans('validation.subcategory_required'),
            'category_list.min' => trans('validation.subcategory_required'),
            'category_list.*.required' => trans('validation.subcategory_required'),
            'category_list.*.exists' => trans('validation.subcategory_required'),
        ];
    }

    protected function withProductCategoryValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parent = (int) $this->input('parent_category_id');
            $ids = array_filter((array) $this->input('category_list', []));

            if (! $parent || empty($ids)) {
                return;
            }

            $validCount = SubCategory::where('category_id', $parent)
                ->whereIn('id', $ids)
                ->count();

            if ($validCount !== count($ids)) {
                $validator->errors()->add(
                    'category_list',
                    trans('validation.subcategory_must_belong_to_category')
                );
            }
        });
    }
}
