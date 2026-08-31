<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetAdminOnlyCommand extends Command
{
    protected $signature = 'hyperlocal:reset-admin-only
                            {--admin-id=1 : User ID to keep as the only admin}
                            {--force : Required to run destructive reset}';

    protected $description = 'Remove all shops, customers, orders, and merchants — keep one platform admin only';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('This will permanently delete all marketplace data.');
            $this->line('Run with --force to confirm: php artisan hyperlocal:reset-admin-only --force');

            return self::FAILURE;
        }

        $adminId = (int) $this->option('admin-id');
        $admin = User::find($adminId);

        if (! $admin || $admin->shop_id) {
            $this->error("Admin user #{$adminId} not found or is a shop user.");

            return self::FAILURE;
        }

        $this->warn("Resetting database — keeping admin: {$admin->email} (ID {$adminId})");
        $this->newLine();

        Schema::disableForeignKeyConstraints();

        $tables = [
            'activity_log',
            'affiliate_commissions',
            'affiliate_links',
            'affiliates',
            'attribute_inventory',
            'avg_feedback',
            'blog_comments',
            'blogs',
            'cancellations',
            'cart_items',
            'carts',
            'chat_socket_events',
            'chat_conversations',
            'messages',
            'config_cybersources',
            'config_instamojo',
            'config_manual_payments',
            'config_mpesa',
            'config_paypal_express',
            'config_paypals',
            'config_stripes',
            'configs',
            'contact_us',
            'coupon_customer',
            'coupon_shipping_zone',
            'coupons',
            'customers',
            'delivery_boys',
            'disputes',
            'email_logs',
            'feedbacks',
            'gift_cards',
            'inventories',
            'invoices',
            'order_items',
            'orders',
            'push_campaigns',
            'refunds',
            'replies',
            'shipping_rates',
            'shipping_zones',
            'shop_payment_methods',
            'shop_shipping_methods',
            'subscriptions',
            'subscription_items',
            'suppliers',
            'user_warehouse',
            'warehouses',
            'category_product',
            'products',
            'manufacturers',
            'shops',
            'tickets',
            'transactions',
            'transfers',
            'wallet_credit_rewards',
            'wallets',
            'translation_inventories',
            'translation_products',
            'translation_manufacturers',
            'translation_shops',
            'wishlists',
            'visitors',
            'notifications',
            'password_resets',
            'oauth_access_tokens',
            'oauth_auth_codes',
            'oauth_refresh_tokens',
            'banners',
            'sliders',
            'attachments',
            'images',
            'taggables',
            'tags',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  Cleared {$table}");
            }
        }

        if (Schema::hasTable('addresses')) {
            DB::table('addresses')
                ->where(function ($q) use ($adminId) {
                    $q->where('addressable_type', '!=', User::class)
                        ->orWhere('addressable_id', '!=', $adminId);
                })
                ->delete();
            $this->line('  Cleared non-admin addresses');
        }

        if (Schema::hasTable('dashboard_configs')) {
            DB::table('dashboard_configs')->where('user_id', '!=', $adminId)->delete();
        }

        $removedUsers = User::where('id', '!=', $adminId)->count();
        User::where('id', '!=', $adminId)->forceDelete();
        $this->line("  Removed {$removedUsers} non-admin users");

        if (Schema::hasTable('options')) {
            DB::table('options')
                ->whereIn('option_name', [
                    'featured_items',
                    'featured_vendors',
                    'featured_brands',
                    'flash_deals',
                    'deal_of_the_day',
                    'best_finds_under',
                ])
                ->orWhere('option_name', 'like', 'featured_items%')
                ->delete();
            $this->line('  Cleared homepage option cache');
        }

        Schema::enableForeignKeyConstraints();

        $this->call('cache:clear');

        $this->newLine();
        $this->info('Database reset complete.');
        $this->table(
            ['Kept', 'Value'],
            [
                ['Admin email', $admin->email],
                ['Admin login', url('/admin/login')],
                ['Shops', '0'],
                ['Customers', '0'],
                ['Orders', '0'],
            ]
        );

        $this->newLine();
        $this->comment('Platform config kept: countries, roles, categories, payment methods, subscription plans.');
        $this->comment('Set GOOGLE_PLACE_KEY in .env for maps/geocoding (see env.example line ~227).');

        return self::SUCCESS;
    }
}
