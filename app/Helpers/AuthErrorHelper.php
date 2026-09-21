<?php

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

if (! function_exists('is_unique_constraint_violation')) {
    /**
     * Detect MySQL / SQLite unique-constraint failures (duplicate email, etc.).
     */
    function is_unique_constraint_violation(\Throwable $e): bool
    {
        if (! $e instanceof QueryException) {
            $message = strtolower($e->getMessage());

            return str_contains($message, 'duplicate entry')
                || str_contains($message, 'unique constraint')
                || str_contains($message, 'integrity constraint violation');
        }

        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        return $sqlState === '23000'
            || $driverCode === 1062
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'integrity constraint violation');
    }
}

if (! function_exists('unique_constraint_field')) {
    /**
     * Guess which request field collided from a DB unique-index error.
     */
    function unique_constraint_field(\Throwable $e, string $fallback = 'email'): string
    {
        $message = strtolower($e->getMessage());

        foreach (['email', 'phone', 'slug', 'username', 'shop_name', 'name'] as $field) {
            if (str_contains($message, $field)) {
                return $field === 'shop_name' ? 'shop_name' : $field;
            }
        }

        return $fallback;
    }
}

if (! function_exists('registration_unique_message')) {
    /**
     * User-facing message when a registration unique field already exists.
     */
    function registration_unique_message(string $field = 'email', ?string $context = null): string
    {
        if ($context === 'affiliate' && $field === 'email') {
            return trans('packages.affiliate.email_already_registered');
        }

        return match ($field) {
            'phone' => trans('validation.register_phone_unique'),
            'shop_name', 'name' => trans('validation.register_shop_name_unique'),
            'slug' => trans('validation.register_slug_unique'),
            default => trans('validation.register_email_unique'),
        };
    }
}

if (! function_exists('throw_registration_unique_validation')) {
    /**
     * Re-throw a duplicate-key failure as a normal validation error.
     *
     * @throws ValidationException
     */
    function throw_registration_unique_validation(\Throwable $e, ?string $context = null): void
    {
        if (! is_unique_constraint_violation($e)) {
            return;
        }

        $field = unique_constraint_field($e);
        $message = registration_unique_message($field, $context);

        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }
}

if (! function_exists('registration_failure_response')) {
    /**
     * Consistent register failure for web redirects and JSON/API clients.
     */
    function registration_failure_response(
        \Throwable $e,
        $redirectTo = null,
        ?string $context = null,
        ?string $fallbackMessage = null
    ): JsonResponse|RedirectResponse {
        $fallbackMessage ??= trans('responses.vendor_config_failed');

        if (is_unique_constraint_violation($e)) {
            $field = unique_constraint_field($e);
            $message = registration_unique_message($field, $context);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'message' => $message,
                    'errors' => [$field => [$message]],
                ], 422);
            }

            $errors = new MessageBag;
            $errors->add($field, $message);

            return redirect()
                ->to($redirectTo ?: url()->previous())
                ->withErrors($errors)
                ->withInput(request()->except(['password', 'password_confirmation']));
        }

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'message' => $fallbackMessage,
                'errors' => ['errors' => [$fallbackMessage]],
            ], 422);
        }

        $errors = new MessageBag;
        $errors->add('errors', $fallbackMessage);

        return redirect()
            ->to($redirectTo ?: url()->previous())
            ->withErrors($errors)
            ->withInput(request()->except(['password', 'password_confirmation']));
    }
}

if (! function_exists('auth_failed_response')) {
    /**
     * Consistent login failure for web and JSON clients.
     */
    function auth_failed_response(string $field = 'email', ?string $message = null): JsonResponse|RedirectResponse
    {
        $message ??= trans('auth.failed');

        if (request()->expectsJson() || request()->ajax() || request()->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [$field => [$message]],
            ], 422);
        }

        return redirect()
            ->back()
            ->withInput(request()->only($field))
            ->withErrors([$field => $message]);
    }
}
