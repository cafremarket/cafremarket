<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class UpdateWebBannerRequest extends Request
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $this->merge(['shop_id' => null]);

        return [
            'group_id' => 'required|in:group_1,group_2,group_3,group_4,group_5,group_6',
            'title' => 'max:255',
            'description' => 'max:255',
            'images.feature' => 'nullable|mimes:jpg,jpeg,png,gif,svg,webp',
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.required' => trans('validation.banner_group_id_required'),
        ];
    }
}
