<?php

namespace App\Common;

use Illuminate\Support\Str;

/**
 * Attach this Trait to a User has ApiAuthTokens
 *
 * @author Munna Khan
 */
trait ApiAuthTokens
{
    public function generateToken()
    {
        // Reuse the existing token so logging in on web/another device
        // does not kick the mobile app out of its session.
        if (! empty($this->api_token)) {
            return $this->api_token;
        }

        $token = Str::random(60);

        $this->api_token = $token;

        $this->save();

        return $token;
    }
}
