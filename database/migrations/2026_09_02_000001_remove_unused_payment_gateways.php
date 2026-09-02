<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ALLOWED_CODES = [
        'stripe',
        'paypal',
        'mpesa',
        'emola',
        'cod',
        'wire',
    ];

    public function up(): void
    {
        $removedIds = DB::table('payment_methods')
            ->whereNotIn('code', self::ALLOWED_CODES)
            ->pluck('id');

        if ($removedIds->isEmpty()) {
            return;
        }

        DB::table('payment_methods')
            ->whereIn('id', $removedIds)
            ->update(['enabled' => 0]);

        $table = function_exists('get_option_table_name')
            ? get_option_table_name()
            : (config('system_settings.option_table_name') ?: 'options');

        $row = DB::table($table)->where('option_name', 'wallet_payment_methods')->first();

        if ($row) {
            $methods = @unserialize($row->option_value);

            if (is_array($methods)) {
                $allowedIds = DB::table('payment_methods')
                    ->whereIn('code', self::ALLOWED_CODES)
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->all();

                $methods = array_values(array_filter(
                    $methods,
                    fn ($id) => in_array((string) $id, $allowedIds, true)
                ));

                DB::table($table)->where('option_name', 'wallet_payment_methods')->update([
                    'option_value' => serialize($methods),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Removed gateways are not restored automatically.
    }
};
