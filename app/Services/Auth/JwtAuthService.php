<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Cookie;

class JwtAuthService
{
    /**
     * Issue a signed JWT and persist jti on the authenticatable row.
     */
    public function issue(Authenticatable $user, string $guard): string
    {
        $config = $this->guardConfig($guard);
        $jti = Str::random(40);

        $user->forceFill(['api_token' => $jti])->save();

        $now = time();
        $ttlMinutes = (int) config('jwt.ttl_minutes', 43200);

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->getAuthIdentifier(),
            'guard' => $guard,
            'jti' => $jti,
            'iat' => $now,
            'exp' => $now + ($ttlMinutes * 60),
        ];

        $jwt = JWT::encode($payload, $this->secret(), config('jwt.algo', 'HS256'));

        if ($user instanceof Model) {
            $user->jwt_access_token = $jwt;
        }

        return $jwt;
    }

    public function invalidate(Authenticatable $user, ?string $guard = null): void
    {
        $guard ??= $this->inferGuardFromUser($user);
        $config = $this->guardConfig($guard);

        $payload = ['api_token' => null];

        if (($config['clear_fcm_on_logout'] ?? false) && $user instanceof Model) {
            $payload['fcm_token'] = null;
        }

        $user->forceFill($payload)->save();
    }

    public function resolve(?string $token, string $guard): ?Authenticatable
    {
        if (! $token) {
            return null;
        }

        if (str_contains($token, '.')) {
            return $this->resolveJwt($token, $guard);
        }

        return $this->resolveLegacyToken($token, $guard);
    }

    public function resolveFromRequest(Request $request, string $guard): ?Authenticatable
    {
        $token = $request->bearerToken();

        if (! $token) {
            $cookie = $this->guardConfig($guard)['cookie'] ?? null;
            if ($cookie) {
                $token = $request->cookie($cookie);
            }
        }

        return $this->resolve($token, $guard);
    }

    public function makeCookie(string $guard, string $token, bool $remember = false): Cookie
    {
        $cookieName = $this->guardConfig($guard)['cookie'] ?? null;

        if (! $cookieName) {
            throw new InvalidArgumentException("Guard [{$guard}] does not use JWT cookies.");
        }

        $minutes = $remember
            ? (int) config('jwt.remember_ttl_minutes', 525600)
            : (int) config('jwt.ttl_minutes', 43200);

        return cookie(
            $cookieName,
            $token,
            $minutes,
            config('jwt.cookie_path', '/'),
            config('jwt.cookie_domain'),
            config('jwt.secure'),
            true,
            false,
            config('jwt.same_site', 'lax')
        );
    }

    public function forgetCookie(string $guard): Cookie
    {
        $cookieName = $this->guardConfig($guard)['cookie'] ?? null;

        if (! $cookieName) {
            throw new InvalidArgumentException("Guard [{$guard}] does not use JWT cookies.");
        }

        return cookie()->forget($cookieName);
    }

    public function inferGuardFromUser(Authenticatable $user): string
    {
        return match ($user::class) {
            \App\Models\Customer::class => 'customer',
            \App\Models\DeliveryBoy::class => 'delivery_boy',
            \Incevio\Package\Affiliate\Models\Affiliate::class => 'affiliate',
            \App\Models\User::class => 'vendor_api',
            default => 'customer',
        };
    }

    protected function resolveJwt(string $token, string $expectedGuard): ?Authenticatable
    {
        try {
            $payload = JWT::decode(
                $token,
                new Key($this->secret(), config('jwt.algo', 'HS256'))
            );
        } catch (\Throwable) {
            return null;
        }

        if (($payload->guard ?? null) !== $expectedGuard) {
            return null;
        }

        $config = $this->guardConfig($expectedGuard);
        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $user = $modelClass::find($payload->sub ?? null);

        if (! $user || ! $this->isActive($user, $config)) {
            return null;
        }

        if (empty($user->api_token) || ($payload->jti ?? null) !== $user->api_token) {
            return null;
        }

        return $user;
    }

    protected function resolveLegacyToken(string $token, string $guard): ?Authenticatable
    {
        $config = $this->guardConfig($guard);
        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];

        $user = $modelClass::query()
            ->where('api_token', $token)
            ->first();

        if (! $user || ! $this->isActive($user, $config)) {
            return null;
        }

        return $user;
    }

    protected function isActive(Model $user, array $config): bool
    {
        $column = $config['active_column'] ?? 'active';

        return (bool) $user->{$column};
    }

    protected function guardConfig(string $guard): array
    {
        $config = config("jwt.guards.{$guard}");

        if (! is_array($config) || empty($config['model'])) {
            throw new InvalidArgumentException("JWT guard [{$guard}] is not configured.");
        }

        return $config;
    }

    protected function secret(): string
    {
        return (string) (config('jwt.secret') ?: config('app.key'));
    }
}
