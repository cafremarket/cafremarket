<?php

namespace App\Repositories\Attribute;

interface AttributeRepository
{
    public function entities($id);

    public function getAttributeTypeId($attribute);
}
