<?php

namespace App\Common;

use App\Services\Auth\JwtAuthService;

/**
 * Attach this Trait to a User has ApiAuthTokens
 *
 * @author Munna Khan
 */
trait ApiAuthTokens
{
    public function generateToken(?string $guard = null)
    {
        $guard ??= app(JwtAuthService::class)->inferGuardFromUser($this);

        return app(JwtAuthService::class)->issue($this, $guard);
    }
}
