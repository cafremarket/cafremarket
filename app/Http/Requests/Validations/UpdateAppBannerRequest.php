<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Banner;
use Illuminate\Validation\Rule;

class UpdateAppBannerRequest extends Request
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'group_id' => 'required|in:group_1,group_2,group_3',
            'title' => 'max:255',
            'description' => 'max:255',
            'hide_text' => 'nullable|boolean',
            'display_type' => ['required', Rule::in([Banner::TYPE_SINGLE, Banner::TYPE_SLIDER, Banner::TYPE_COLOUR])],
            'columns' => ['required', Rule::in([Banner::LAYOUT_FULL, Banner::LAYOUT_THIRD])],
            'bg_color' => 'nullable|string|max:20',
            'images.feature' => 'nullable|mimes:jpg,jpeg,png,gif,svg,webp',
        ];
    }

    protected function prepareForValidation(): void
    {
        $displayType = $this->input('display_type', Banner::TYPE_SINGLE);
        $columns = (int) $this->input('columns', Banner::LAYOUT_FULL);

        if ($displayType === Banner::TYPE_SLIDER) {
            $columns = Banner::LAYOUT_FULL;
        }

        $this->merge([
            'shop_id' => null,
            'channel' => Banner::CHANNEL_APP,
            'hide_text' => $this->boolean('hide_text'),
            'display_type' => $displayType,
            'columns' => $columns,
            'bg_color' => $this->input('bg_color') ?: null,
        ]);
    }

    public function messages(): array
    {
        return [
            'group_id.required' => trans('validation.banner_group_id_required'),
        ];
    }
}
