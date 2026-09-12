<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiMobilePayloadTest extends TestCase
{
    public function test_null_gender_is_serialized_as_null_not_empty_object()
    {
        $this->assertNull(get_formated_gender(null, false));
        $this->assertNull(get_formated_gender('', false));
        $this->assertIsString(get_formated_gender('app.male', false));
    }
}
