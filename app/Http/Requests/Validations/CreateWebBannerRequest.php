<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class CreateWebBannerRequest extends Request
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'group_id' => 'required|in:group_1,group_2,group_3,group_4,group_5,group_6',
            'title' => 'max:255',
            'description' => 'max:255',
            'hide_text' => 'nullable|boolean',
            'images.feature' => 'required|mimes:jpg,jpeg,png,gif,svg,webp',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shop_id' => null,
            'hide_text' => $this->boolean('hide_text'),
        ]);
    }

    public function messages(): array
    {
        return [
            'group_id.required' => trans('validation.banner_group_id_required'),
            'images.feature.required' => trans('validation.banner_image_required'),
        ];
    }
}
