<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\FakeAccountDetector;
use App\Services\Auth\VerificationHealth;
use Illuminate\Support\Facades\Cache;

/**
 * Admin view of the anti-fake-account checks: what's on, what's broken,
 * and what still needs setting up.
 */
class VerificationReportController extends Controller
{
    public function index(FakeAccountDetector $detector)
    {
        try {
            $mail = VerificationHealth::mail();
        } catch (\Throwable $e) {
            VerificationHealth::logIssue('report', 'Mail status unavailable: '.$e->getMessage());
            $mail = ['status' => 'failing', 'last_failure' => null, 'last_sent' => null];
        }

        // Each section degrades to empty rather than breaking the page.
        $safe = function (callable $fn, $fallback, string $what) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                VerificationHealth::logIssue('report', "Could not load {$what}: ".$e->getMessage());

                return $fallback;
            }
        };

        $since = now()->subDays(30);

        $recentMailFailures = $safe(fn () => email_logs_ready()
            ? EmailLog::where('status', EmailLog::STATUS_FAILED)->latest('id')->limit(10)->get()
            : collect(), collect(), 'mail failures');

        return view('admin.report.platform.verification', [
            'mail' => $mail,
            'enforced' => $mail['status'] === 'working',
            'smsConfigured' => VerificationHealth::smsConfigured(),
            'recaptchaConfigured' => VerificationHealth::recaptchaConfigured(),
            'recaptchaFailure' => VerificationHealth::lastRecaptchaFailure(),
            'recentMailFailures' => $recentMailFailures,
            'recentIssues' => VerificationHealth::recentIssues(),
            'customers' => $safe(fn () => [
                'new' => Customer::where('created_at', '>=', $since)->count(),
                'unverified' => Customer::where('created_at', '>=', $since)->whereNotNull('verification_token')->count(),
            ], ['new' => 0, 'unverified' => 0], 'customer counts'),
            'vendors' => $safe(fn () => [
                'new' => User::where('role_id', Role::MERCHANT)->where('created_at', '>=', $since)->count(),
                'unverified' => User::where('role_id', Role::MERCHANT)->where('created_at', '>=', $since)->whereNotNull('verification_token')->count(),
            ], ['new' => 0, 'unverified' => 0], 'vendor counts'),
            'unverifiedCustomers' => $safe(fn () => Customer::whereNotNull('verification_token')->latest('id')->limit(15)->get(['id', 'name', 'email', 'created_at']), collect(), 'unverified customers'),
            'suspects' => $safe(fn () => $this->cachedScan('customers', fn () => array_map(fn ($s) => [
                'id' => $s['customer']->id,
                'name' => $s['customer']->name,
                'email' => $s['customer']->email,
                'created_at' => (string) $s['customer']->created_at,
                'score' => $s['score'],
                'reasons' => $s['reasons'],
            ], $detector->suspects(100))), [], 'suspected fake customers'),
            'suspectVendors' => $safe(fn () => $this->cachedScan('vendors', fn () => array_map(fn ($s) => [
                'id' => $s['user']->id,
                'name' => $s['user']->name,
                'email' => $s['user']->email,
                'reasons' => $s['reasons'],
            ], $detector->suspectVendors())), [], 'suspected fake vendors'),
        ]);
    }

    /**
     * The scan does DNS lookups per domain, so it is cached for 30 minutes
     * (?refresh=1 forces a new scan).
     */
    private function cachedScan(string $what, callable $scan): array
    {
        $key = 'verification_report:scan:'.$what;

        try {
            if (request()->boolean('refresh')) {
                Cache::forget($key);
            }

            return Cache::remember($key, now()->addMinutes(30), $scan);
        } catch (\Throwable $e) {
            return $scan();
        }
    }
}
