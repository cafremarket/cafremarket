<?php

namespace Incevio\Package\DynamicPopup\Enums;

class PopupTypes
{
    /**
     * The list of popup types
     *
     * @return array
     */
    public static function list(): array
    {
        return [
            "newsletter" => trans('DynamicPopup::lang.newsletter'),
            "banner" => trans('DynamicPopup::lang.banner'),
            "none" => trans('DynamicPopup::lang.none')
        ];
    }
}
