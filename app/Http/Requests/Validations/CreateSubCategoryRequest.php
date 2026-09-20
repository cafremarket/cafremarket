<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class CreateSubCategoryRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() && $this->user()->isFromPlatform();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'category_id' => 'required|integer',
            'name' => 'required',
            'slug' => 'required|alpha_dash|unique:sub_categories',
            'active' => 'required',
        ];
    }
}
