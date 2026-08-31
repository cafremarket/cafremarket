<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Console\Command;

class ReleaseTrashedMerchantIdentifiers extends Command
{
    protected $signature = 'merchant:release-trashed-identifiers';

    protected $description = 'Release email, slug, and shop name from soft-deleted merchants and shops so they can be reused';

    public function handle(): int
    {
        $users = 0;
        $shops = 0;

        User::onlyTrashed()->chunkById(100, function ($chunk) use (&$users) {
            foreach ($chunk as $user) {
                $user->releaseUniqueIdentifiers();
                $user->saveQuietly();
                $users++;
            }
        });

        Shop::onlyTrashed()->chunkById(100, function ($chunk) use (&$shops) {
            foreach ($chunk as $shop) {
                $shop->releaseUniqueIdentifiers();
                $shop->saveQuietly();
                $shops++;
            }
        });

        $this->info("Released identifiers for {$users} trashed user(s) and {$shops} trashed shop(s).");

        return self::SUCCESS;
    }
}
