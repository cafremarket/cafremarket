<?php

namespace App\Services\Auth;

use App\Models\Customer;
use App\Models\User;
use App\Rules\RealEmail;
use App\Rules\RealPhone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Finds customer accounts that look fake so they can be moved to trash.
 *
 * Only accounts with no real activity are ever considered: no orders,
 * reviews, chats, disputes or wallet money, and not the customer side of a
 * vendor account. Trashing is a soft delete, so an admin can restore it and
 * the real owner of the email can still sign up again.
 */
class FakeAccountDetector
{
    /** Score at which an account counts as fake. */
    public const THRESHOLD = 3;

    /** Unverified accounts older than this (days) are removed while mail works. */
    public const UNVERIFIED_GRACE_DAYS = 7;

    private array $domainCache = [];

    /**
     * Customers with no activity at all; the only ones we ever touch.
     */
    public function inactiveCustomers(): Builder
    {
        $customers = (new Customer)->getTable();

        $query = Customer::query()
            ->whereNotExists(fn ($q) => $q->from('orders')->whereColumn('orders.customer_id', "$customers.id"))
            // The customer account that belongs to a vendor.
            ->whereNotExists(fn ($q) => $q->from('users')->whereColumn('users.email', "$customers.email"));

        foreach (['feedbacks', 'chat_conversations', 'disputes'] as $table) {
            if ($this->hasColumn($table, 'customer_id')) {
                $query->whereNotExists(fn ($q) => $q->from($table)->whereColumn("$table.customer_id", "$customers.id"));
            }
        }

        if ($this->hasColumn('wallets', 'holder_id')) {
            $query->whereNotExists(fn ($q) => $q->from('wallets')
                ->whereColumn('wallets.holder_id', "$customers.id")
                ->where('wallets.holder_type', Customer::class)
                ->where('wallets.balance', '>', 0));
        }

        return $query;
    }

    /**
     * @return array{score: int, reasons: string[]}
     */
    public function assess(Customer $customer): array
    {
        $score = 0;
        $reasons = [];
        $email = Str::lower((string) $customer->email);
        $local = Str::before($email, '@');
        $domain = Str::after($email, '@');

        if ($email === '' || ! str_contains($email, '@')) {
            $score += 3;
            $reasons[] = 'no valid email';
        } elseif ($this->domainStatus($domain) === 'disposable') {
            $score += 3;
            $reasons[] = 'disposable email ('.$domain.')';
        } elseif ($this->domainStatus($domain) === 'dead') {
            $score += 3;
            $reasons[] = 'email domain cannot receive mail ('.$domain.')';
        }

        if (filled($customer->phone) && ! RealPhone::isPlausible((string) $customer->phone)) {
            $score += 2;
            $reasons[] = 'fake phone ('.$customer->phone.')';
        } else {
            $badAddressPhone = $customer->addresses
                ->pluck('phone')
                ->filter()
                ->first(fn ($phone) => ! RealPhone::isPlausible((string) $phone));

            if ($badAddressPhone) {
                $score += 2;
                $reasons[] = 'fake address phone ('.$badAddressPhone.')';
            }
        }

        if (preg_match('/^(test|teste|testing|fake|asdf|qwer|qwerty|abc|abcd|xyz|aaa+|xxx+|demo|sample|dummy|temp|spam)[\d._-]*$/', $local)
            || preg_match('/^\d{6,}$/', $local)
            || $this->looksRandom($local)) {
            $score += 1;
            $reasons[] = 'test/random email name';
        }

        if ($this->looksLikeGibberishName((string) $customer->name)) {
            $score += 1;
            $reasons[] = 'gibberish name';
        }

        return ['score' => $score, 'reasons' => $reasons];
    }

    /**
     * Inactive customers scoring at or above the threshold.
     *
     * @return array<int, array{customer: Customer, score: int, reasons: string[]}>
     */
    public function suspects(?int $limit = null): array
    {
        $found = [];

        $this->inactiveCustomers()->with('addresses')
            ->chunkById(500, function ($chunk) use (&$found, $limit) {
                foreach ($chunk as $customer) {
                    $result = $this->assess($customer);

                    if ($result['score'] >= self::THRESHOLD) {
                        $found[] = ['customer' => $customer] + $result;

                        if ($limit && count($found) >= $limit) {
                            return false;
                        }
                    }
                }
            });

        return $found;
    }

    /**
     * Inactive customers who never verified their email within the grace period.
     * Only meaningful while mail works: otherwise they couldn't have verified.
     */
    public function expiredUnverified(): Builder
    {
        return $this->inactiveCustomers()
            ->whereNotNull('verification_token')
            ->where('created_at', '<', now()->subDays(self::UNVERIFIED_GRACE_DAYS));
    }

    /**
     * Soft-delete the given customers. Never throws; failures are logged.
     *
     * @param  iterable<Customer>  $customers
     */
    public function trash(iterable $customers, string $reason): int
    {
        $count = 0;

        foreach ($customers as $customer) {
            try {
                $customer->delete();
                $count++;

                VerificationHealth::logIssue('cleanup', 'Moved fake account to trash', [
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'reason' => $reason,
                ]);
            } catch (\Throwable $e) {
                VerificationHealth::logIssue('cleanup', 'Could not trash customer #'.$customer->id.': '.$e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Vendors with a disposable/dead email or fake phone. Listed for an admin
     * to review; never removed automatically because they own shops.
     */
    public function suspectVendors(int $limit = 50): array
    {
        $found = [];

        User::query()->whereHas('owns')->latest('id')->limit(500)->get(['id', 'name', 'email', 'phone', 'created_at'])
            ->each(function (User $user) use (&$found, $limit) {
                $reasons = [];
                $domain = Str::lower(Str::after((string) $user->email, '@'));
                $status = $this->domainStatus($domain);

                if ($status === 'disposable') {
                    $reasons[] = 'disposable email ('.$domain.')';
                } elseif ($status === 'dead') {
                    $reasons[] = 'email domain cannot receive mail ('.$domain.')';
                }

                if (filled($user->phone) && ! RealPhone::isPlausible((string) $user->phone)) {
                    $reasons[] = 'fake phone ('.$user->phone.')';
                }

                if ($reasons) {
                    $found[] = ['user' => $user, 'reasons' => $reasons];
                }

                return count($found) < $limit;
            });

        return $found;
    }

    /**
     * @return string ok | disposable | dead
     */
    private function domainStatus(string $domain): string
    {
        if (isset($this->domainCache[$domain])) {
            return $this->domainCache[$domain];
        }

        try {
            $status = RealEmail::isDisposable($domain)
                ? 'disposable'
                : (RealEmail::domainAcceptsMail($domain) ? 'ok' : 'dead');
        } catch (\Throwable $e) {
            // Can't tell: never punish an account on a failed lookup.
            VerificationHealth::logIssue('email-check', 'Domain check failed for '.$domain.': '.$e->getMessage());
            $status = 'ok';
        }

        return $this->domainCache[$domain] = $status;
    }

    private function looksRandom(string $local): bool
    {
        $letters = preg_replace('/[^a-z]/', '', $local);

        // Long run with almost no vowels, e.g. "xkcdqwrtplm".
        return strlen($letters) >= 10 && preg_match_all('/[aeiou]/', $letters) <= 1;
    }

    private function looksLikeGibberishName(string $name): bool
    {
        $letters = preg_replace('/[^\p{L}]/u', '', Str::lower($name));

        if (mb_strlen($letters) < 2) {
            return true;
        }

        return preg_match('/(.)\1{3,}/u', $letters)
            || (mb_strlen($letters) >= 6 && ! preg_match('/[aeiouyáéíóúâêôãõà]/u', $letters));
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
