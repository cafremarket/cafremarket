<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Only these three gateways are offered to buyers now: M-Pesa, eMola,
    // and the platform's own wallet (shown to customers as "Cafrepay").
    private const ALLOWED_CODES = [
        'mpesa',
        'emola',
        'zcart-wallet',
    ];

    public function up(): void
    {
        DB::table('payment_methods')
            ->whereNotIn('code', self::ALLOWED_CODES)
            ->update(['enabled' => 0]);

        DB::table('payment_methods')
            ->whereIn('code', self::ALLOWED_CODES)
            ->update(['enabled' => 1]);

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
