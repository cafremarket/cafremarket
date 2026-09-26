<?php

namespace App\Console\Commands;

use App\Services\Auth\FakeAccountDetector;
use App\Services\Auth\VerificationHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lists (default) or trashes (--apply) customer accounts that look fake.
 * Accounts with any orders, reviews, chats, disputes or wallet money are
 * never touched. Trashed accounts can be restored from the admin panel.
 */
class CleanFakeAccounts extends Command
{
    protected $signature = 'accounts:clean-fake
        {--apply : Move the accounts to trash (without this it is a dry run)}
        {--unverified-only : Only remove accounts that never verified their email within the grace period}
        {--limit=0 : Stop after this many suspects (0 = no limit)}';

    protected $description = 'Find fake/abandoned customer accounts and move them to trash';

    public function handle(FakeAccountDetector $detector): int
    {
        $apply = (bool) $this->option('apply');
        $limit = (int) $this->option('limit') ?: null;

        try {
            $total = 0;

            if (! $this->option('unverified-only')) {
                $suspects = $detector->suspects($limit);

                $this->table(['ID', 'Email', 'Name', 'Score', 'Why'], array_map(fn ($s) => [
                    $s['customer']->id,
                    $s['customer']->email,
                    $s['customer']->name,
                    $s['score'],
                    implode('; ', $s['reasons']),
                ], $suspects));

                $this->info(count($suspects).' suspected fake account(s).');

                if ($apply) {
                    $total += $detector->trash(array_column($suspects, 'customer'), 'fake-looking account');
                }
            }

            if (! $this->grandfatherMigrationRan()) {
                // Before it runs, every old account still looks "unverified".
                $this->warn('Run "php artisan migrate" first; until then unverified accounts are skipped.');
            } elseif (VerificationHealth::emailVerificationEnforced()) {
                $expired = $detector->expiredUnverified()->limit($limit ?? 10000)->get();
                $this->info($expired->count().' account(s) never verified their email within '.FakeAccountDetector::UNVERIFIED_GRACE_DAYS.' days.');

                if ($apply) {
                    $total += $detector->trash($expired, 'email never verified');
                }
            } else {
                $this->warn('Mail is not working, so unverified accounts are kept (they could not have verified).');
            }

            $this->info($apply
                ? "Moved {$total} account(s) to trash. Restore from Admin > Customers > Trash if needed."
                : 'Dry run only. Re-run with --apply to move these accounts to trash.');
        } catch (\Throwable $e) {
            VerificationHealth::logIssue('cleanup', 'Fake account cleanup failed: '.$e->getMessage());
            $this->error('Cleanup failed; details in storage/logs/verification.log');
        }

        return self::SUCCESS;
    }

    private function grandfatherMigrationRan(): bool
    {
        try {
            return DB::table('migrations')
                ->where('migration', '2026_09_27_100000_grandfather_existing_email_verification')
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
