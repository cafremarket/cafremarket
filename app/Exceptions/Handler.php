<?php

namespace App\Exceptions;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @return void
     */
    public function report(Throwable $exception)
    {
        if (function_exists('is_mail_transport_error') && is_mail_transport_error($exception)) {
            Log::channel('mail')->warning('Mail transport error reported: '.$exception->getMessage());

            if (function_exists('log_email_event')) {
                log_email_event([
                    'status' => \App\Models\EmailLog::STATUS_FAILED,
                    'error' => $exception->getMessage(),
                    'context' => 'ExceptionHandler',
                    'meta' => ['exception' => $exception::class],
                ]);
            }

            if (function_exists('notify_super_admin_mail_failure')) {
                notify_super_admin_mail_failure($exception->getMessage(), 'ExceptionHandler');
            }
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        // Never surface SMTP / recipient rejection errors as 500 pages.
        if (function_exists('is_mail_transport_error') && is_mail_transport_error($exception)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'mail_warning' => true,
                    'message' => trans('messages.mail_send_failed_soft'),
                ], 200);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('warning', trans('messages.mail_send_failed_soft'));
        }

        if ($request->expectsJson()) {
            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['error' => trans('responses.resource_not_found')], 404);
            }
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => trans('responses.unauthenticated')], 401);
        }

        $guard = Arr::get($exception->guards(), 0);

        switch ($guard) {
            case 'customer':
                $login = 'customer.login';
                break;
            default:
                $login = 'login';
                break;
        }

        return redirect()->guest(route($login));
    }

    public function handle($request, Closure $next, $guard = null)
    {
        switch ($guard) {
            case 'customer':
                if (Auth::guard($guard)->check()) {
                    return redirect()->route('customer.dashboard');
                }
                break;
            case 'user':
                if (Auth::guard($guard)->check()) {
                    return redirect()->route('admin.dashboard');
                }
                break;
            default:
                if (Auth::guard($guard)->check()) {
                    return redirect('/');
                }
                break;
        }

        return $next($request);
    }
}
