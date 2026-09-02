<?php

namespace App\Services\Auth;

use App\Models\Customer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * @deprecated Use JwtAuthService directly. Kept for backward compatibility.
 */
class CustomerJwtService
{
    public function issue(Customer $customer): string
    {
        return app(JwtAuthService::class)->issue($customer, 'customer');
    }

    public function invalidate(Customer $customer): void
    {
        app(JwtAuthService::class)->invalidate($customer, 'customer');
    }

    public function resolveCustomer(?string $token): ?Customer
    {
        $user = app(JwtAuthService::class)->resolve($token, 'customer');

        return $user instanceof Customer ? $user : null;
    }

    public function resolveFromRequest(Request $request): ?Customer
    {
        $user = app(JwtAuthService::class)->resolveFromRequest($request, 'customer');

        return $user instanceof Customer ? $user : null;
    }

    public function makeCookie(string $token, bool $remember = false): Cookie
    {
        return app(JwtAuthService::class)->makeCookie('customer', $token, $remember);
    }

    public function forgetCookie(): Cookie
    {
        return app(JwtAuthService::class)->forgetCookie('customer');
    }
}
