<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST auth/email/verify {code} and POST auth/email/resend for the apps.
 */
trait VerifiesAccountEmail
{
    abstract protected function emailVerificationGuard(): string;

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:10']);

        $account = $request->user($this->emailVerificationGuard());
        $result = $account->verifyEmailCode($request->input('code'));

        if ($result === 'verified') {
            return response()->json([
                'message' => trans('auth.verification_successful'),
                'email_verified' => true,
                'email_verification_required' => false,
            ]);
        }

        return response()->json([
            'message' => trans('auth.email_code_'.$result),
            'error_code' => 'email_code_'.$result,
            'errors' => ['code' => [trans('auth.email_code_'.$result)]],
        ], 422);
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $account = $request->user($this->emailVerificationGuard());

        if ($account->hasVerifiedEmail()) {
            return response()->json([
                'message' => trans('auth.email_already_verified'),
                'email_verified' => true,
                'email_verification_required' => false,
            ]);
        }

        $wait = $account->sendEmailVerification();

        // Mail is down: the failure is in the admin verification report; the
        // user is simply not asked to verify until it works again.
        if ($wait === $account::EMAIL_CODE_NOT_SENT) {
            return response()->json([
                'message' => '',
                'email_verified' => false,
                'email_verification_required' => false,
            ]);
        }

        if ($wait > 0) {
            return response()->json([
                'message' => trans('auth.email_code_resend_wait', ['seconds' => $wait]),
                'retry_after' => $wait,
            ], 429);
        }

        return response()->json([
            'message' => trans('auth.verification_code_sent', ['email' => $account->email]),
            'email_verified' => false,
            'email_verification_required' => $account->needsEmailVerification(),
            'retry_after' => $account::$emailCodeResendSeconds,
        ]);
    }
}
