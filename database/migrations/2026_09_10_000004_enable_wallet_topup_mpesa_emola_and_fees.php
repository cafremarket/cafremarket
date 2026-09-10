<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Wallet top-up gateways only (never zcart-wallet). */
    private const DEPOSIT_CODES = [
        'mpesa',
        'emola',
    ];

    public function up(): void
    {
        $now = now();

        DB::table('payment_methods')
            ->whereIn('code', self::DEPOSIT_CODES)
            ->update(['enabled' => 1, 'updated_at' => $now]);

        $methodIds = DB::table('payment_methods')
            ->whereIn('code', self::DEPOSIT_CODES)
            ->orderByRaw("FIELD(code, 'mpesa', 'emola')")
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $table = function_exists('get_option_table_name')
            ? get_option_table_name()
            : (config('system_settings.option_table_name') ?: 'options');

        if (! DB::getSchemaBuilder()->hasTable($table) || $methodIds === []) {
            return;
        }

        $this->upsertOption($table, 'wallet_payment_methods', serialize($methodIds), $now);

        // Enable gateway transaction charges on wallet top-up (keep existing rate/type).
        foreach (['mpesa', 'emola'] as $code) {
            $this->upsertOption($table, "platform_fee_{$code}_enabled", '1', $now, overwrite: true);

            $typeKey = "platform_fee_{$code}_type";
            if (! DB::table($table)->where('option_name', $typeKey)->exists()) {
                $this->upsertOption($table, $typeKey, 'percent', $now);
            }

            $valueKey = "platform_fee_{$code}_value";
            if (! DB::table($table)->where('option_name', $valueKey)->exists()) {
                $this->upsertOption($table, $valueKey, '0', $now);
            }
        }

        Cache::forget('wallet_payment_methods');
        Cache::forget('platform_fee_mpesa_enabled');
        Cache::forget('platform_fee_emola_enabled');
        Cache::forget('system_settings');
    }

    public function down(): void
    {
        $table = function_exists('get_option_table_name')
            ? get_option_table_name()
            : (config('system_settings.option_table_name') ?: 'options');

        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        foreach (['mpesa', 'emola'] as $code) {
            DB::table($table)->where('option_name', "platform_fee_{$code}_enabled")->update([
                'option_value' => '0',
                'updated_at' => now(),
            ]);
            Cache::forget("platform_fee_{$code}_enabled");
        }

        Cache::forget('wallet_payment_methods');
    }

    private function upsertOption(string $table, string $name, string $value, $now, bool $overwrite = false): void
    {
        $existing = DB::table($table)->where('option_name', $name)->first();

        if ($existing) {
            if ($overwrite || $name === 'wallet_payment_methods') {
                DB::table($table)->where('option_name', $name)->update([
                    'option_value' => $value,
                    'autoload' => 1,
                    'updated_at' => $now,
                ]);
            }

            return;
        }

        DB::table($table)->insert([
            'option_name' => $name,
            'option_value' => $value,
            'autoload' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
