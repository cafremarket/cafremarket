<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Wipe all marketplace data (shops, merchants, customers, products, orders,
 * wallets, chats, logs, ...) while keeping categories, one admin user and
 * platform configuration.
 *
 * Works from a KEEP list: every table not listed below is truncated, so new
 * tables added by future migrations are reset automatically.
 */
class ResetKeepCategoriesCommand extends Command
{
    protected $signature = 'cafrepay:reset-keep-categories
                            {--admin-id=1 : User ID of the admin to keep}
                            {--dry-run : Show what would be cleared without changing anything}
                            {--force : Required to run the destructive reset}';

    protected $description = 'Reset the whole system — keep categories, the admin user and platform settings only';

    /** Tables kept untouched. */
    protected array $keep = [
        // Categories
        'categories', 'sub_categories', 'translation_categories', 'translation_sub_categories',
        // Access control
        'roles', 'permissions', 'permission_role',
        // Platform settings & reference data
        'systems', 'options', 'modules', 'packages', 'migrations',
        'countries', 'states', 'currencies', 'timezones', 'languages',
        'payment_methods', 'subscription_plans',
        'address_types', 'attribute_types', 'gtin_types', 'banner_groups',
        'cancellation_reasons', 'dispute_types', 'ticket_categories',
        'pages', 'faqs', 'faq_topics', 'popups',
        'oauth_clients', 'oauth_personal_access_clients',
    ];

    /** Tables where only admin/platform rows are kept (handled in cleanPartial()). */
    protected array $partial = [
        'users', 'addresses', 'images', 'attachments', 'dashboard_configs',
        'banners', 'email_templates', 'pdf_templates', 'taxes', 'carriers',
    ];

    /** Options that point at products / shops / brands — reset to empty. */
    protected array $staleOptions = [
        'featured_items', 'flashdeal_items', 'deal_of_the_day',
        'featured_brands', 'featured_vendors', 'featured_shops',
        'best_finds_under', 'best_finds_under1', 'best_finds_under2',
        'best_finds_under3', 'best_finds_under4',
    ];

    /** Image owners that survive. */
    protected array $keepImageTypes = [
        'App\\Models\\Category', 'App\\Models\\SubCategory', 'App\\Models\\System',
        'App\\Models\\Popup', 'App\\Models\\Banner',
    ];

    /** Storage folders holding only per-user uploads — emptied completely. */
    protected array $wipeDirs = ['payout-proofs'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            $this->error('This permanently deletes all shops, customers, products and orders.');
            $this->line('Preview:  php artisan cafrepay:reset-keep-categories --dry-run');
            $this->line('Run:      php artisan cafrepay:reset-keep-categories --force');

            return self::FAILURE;
        }

        $adminId = (int) $this->option('admin-id');
        $admin = User::find($adminId);

        if (! $admin || $admin->shop_id || ! in_array((int) $admin->role_id, [1, 2], true)) {
            $this->error("User #{$adminId} is not a platform admin (role Super Admin/Admin without a shop).");

            return self::FAILURE;
        }

        $truncate = collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->reject(fn ($t) => in_array($t, $this->keep, true) || in_array($t, $this->partial, true))
            ->values();

        $this->warn(($dryRun ? '[DRY RUN] ' : '')."Keeping admin {$admin->email} (#{$adminId}), categories and platform settings.");
        $this->line('Tables to empty: '.$truncate->count());
        foreach ($truncate as $table) {
            $rows = DB::table($table)->count();
            $this->line("  {$table}".($rows ? " ({$rows} rows)" : ''));
        }
        $this->line('Partially cleaned: '.implode(', ', $this->partial));
        $this->line('Options reset: '.implode(', ', $this->staleOptions));

        if ($dryRun) {
            $this->comment('Dry run — nothing changed.');

            return self::SUCCESS;
        }

        // Collect files of image/attachment rows that are about to go, before deleting the rows.
        $files = $this->filesToDelete($adminId);

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($truncate as $table) {
                DB::table($table)->truncate();
            }
            $this->cleanPartial($adminId);
            $this->cleanOptions();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
        $this->info('Database cleaned.');

        $disk = Storage::disk('public');
        $deleted = 0;
        foreach ($files as $path) {
            if ($path && $disk->exists($path) && $disk->delete($path)) {
                $deleted++;
            }
        }
        foreach ($this->wipeDirs as $dir) {
            File::cleanDirectory($disk->path($dir));
        }
        $this->info("Deleted {$deleted} uploaded files.");

        // Search indexes still hold deleted products/customers.
        foreach (['App\\Models\\Product', 'App\\Models\\Inventory', 'App\\Models\\Customer'] as $model) {
            $this->callSilently('scout:flush', ['model' => $model]);
        }
        $this->call('cache:clear');

        $this->newLine();
        $this->table(['Kept', 'Count'], [
            ['Admin', $admin->email],
            ['Categories', DB::table('categories')->count()],
            ['Sub-categories', DB::table('sub_categories')->count()],
            ['Users', DB::table('users')->count()],
            ['Shops / Customers / Orders', '0 / 0 / 0'],
        ]);
        $this->comment('Admin login: '.url('/admin/login'));

        return self::SUCCESS;
    }

    protected function cleanPartial(int $adminId): void
    {
        User::withTrashed()->where('id', '!=', $adminId)->forceDelete();

        DB::table('addresses')
            ->where('addressable_type', '!=', 'App\\Models\\System')
            ->where(fn ($q) => $q->where('addressable_type', '!=', User::class)
                ->orWhere('addressable_id', '!=', $adminId))
            ->delete();

        $this->imagesToDeleteQuery($adminId)->delete();
        DB::table('attachments')->where('attachable_type', '!=', 'App\\Models\\System')->delete();
        DB::table('dashboard_configs')->where('user_id', '!=', $adminId)->delete();

        // Shop-owned rows go; platform-owned (shop_id NULL) rows stay.
        foreach (['banners', 'email_templates', 'pdf_templates', 'taxes', 'carriers'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereNotNull('shop_id')->delete();
            }
        }
    }

    protected function cleanOptions(): void
    {
        DB::table('options')->whereIn('option_name', $this->staleOptions)->delete();
    }

    protected function imagesToDeleteQuery(int $adminId)
    {
        return DB::table('images')
            ->whereNotIn('imageable_type', $this->keepImageTypes)
            ->where(fn ($q) => $q->where('imageable_type', '!=', User::class)
                ->orWhere('imageable_id', '!=', $adminId))
            // Banners owned by a shop are deleted, so their images go too.
            ->orWhere(fn ($q) => $q->where('imageable_type', 'App\\Models\\Banner')
                ->whereIn('imageable_id', DB::table('banners')->whereNotNull('shop_id')->select('id')));
    }

    protected function filesToDelete(int $adminId): array
    {
        $images = $this->imagesToDeleteQuery($adminId)->pluck('path')->all();
        $attachments = DB::table('attachments')
            ->where('attachable_type', '!=', 'App\\Models\\System')
            ->pluck('path')->all();

        return array_merge($images, $attachments);
    }
}
