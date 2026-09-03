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
    /**
     * In-memory JWT for API responses — never persisted (no DB column).
     */
    public function setTransientJwtAccessToken(string $jwt): static
    {
        $this->attributes['jwt_access_token'] = $jwt;

        return $this;
    }

    /**
     * Prevent transient JWT from being written on unrelated model saves.
     */
    public function getDirty()
    {
        return collect(parent::getDirty())
            ->except(['jwt_access_token'])
            ->all();
    }

    public function generateToken(?string $guard = null)
    {
        $guard ??= app(JwtAuthService::class)->inferGuardFromUser($this);

        return app(JwtAuthService::class)->issue($this, $guard);
    }
}
