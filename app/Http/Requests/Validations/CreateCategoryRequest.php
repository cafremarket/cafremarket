<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateCategoryRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() && $this->user()->isFromMerchant() && $this->user()->merchantId();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $shopId = Auth::user()->merchantId();

        $slug = $this->input('slug');
        if ($shopId && filled($slug)) {
            if (! str_ends_with($slug, '-s'.$shopId) && ! str_contains($slug, '-s'.$shopId.'-')) {
                $slug .= '-s'.$shopId;
            }
        } elseif ($shopId && blank($slug) && $this->filled('name')) {
            $slug = Str::slug($this->input('name')).'-s'.$shopId;
        }

        $this->merge([
            'shop_id' => $shopId,
            'slug' => $slug,
            'category_sub_group_id' => ensure_default_category_sub_group_id(),
            'meta_title' => null,
            'meta_description' => null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'category_sub_group_id' => 'required|integer',
            'name' => 'required',
            'slug' => 'required|alpha_dash|unique:categories',
            'image' => 'mimes:jpg,jpeg,png,svg',
            'active' => 'required',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
