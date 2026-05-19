<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $emolaId = DB::table('payment_methods')->where('code', 'emola')->value('id');

        if (! $emolaId) {
            return;
        }

        $table = function_exists('get_option_table_name')
            ? get_option_table_name()
            : (config('system_settings.option_table_name') ?: 'options');

        $row = DB::table($table)->where('option_name', 'wallet_payment_methods')->first();

        if (! $row) {
            return;
        }

        $methods = @unserialize($row->option_value);

        if (! is_array($methods)) {
            $methods = [];
        }

        $id = (string) $emolaId;

        if (! in_array($id, $methods, true)) {
            $methods[] = $id;
            DB::table($table)->where('option_name', 'wallet_payment_methods')->update([
                'option_value' => serialize(array_values($methods)),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $emolaId = DB::table('payment_methods')->where('code', 'emola')->value('id');

        if (! $emolaId) {
            return;
        }

        $table = function_exists('get_option_table_name')
            ? get_option_table_name()
            : (config('system_settings.option_table_name') ?: 'options');

        $row = DB::table($table)->where('option_name', 'wallet_payment_methods')->first();

        if (! $row) {
            return;
        }

        $methods = @unserialize($row->option_value);

        if (! is_array($methods)) {
            return;
        }

        $methods = array_values(array_filter($methods, fn ($v) => (string) $v !== (string) $emolaId));

        DB::table($table)->where('option_name', 'wallet_payment_methods')->update([
            'option_value' => serialize($methods),
            'updated_at' => now(),
        ]);
    }
};
