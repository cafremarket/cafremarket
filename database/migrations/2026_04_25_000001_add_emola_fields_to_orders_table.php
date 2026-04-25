<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'emola_trans_id')) {
                $table->string('emola_trans_id', 30)->nullable()->after('payment_ref_id');
            }
            if (! Schema::hasColumn('orders', 'emola_request_id')) {
                $table->string('emola_request_id', 64)->nullable()->after('emola_trans_id');
            }
            if (! Schema::hasColumn('orders', 'emola_ref_no')) {
                $table->string('emola_ref_no', 20)->nullable()->after('emola_request_id');
            }
            if (! Schema::hasColumn('orders', 'emola_error_code')) {
                $table->string('emola_error_code', 10)->nullable()->after('emola_ref_no');
            }
            if (! Schema::hasColumn('orders', 'emola_message')) {
                $table->string('emola_message', 255)->nullable()->after('emola_error_code');
            }
            if (! Schema::hasColumn('orders', 'emola_gwtransid')) {
                $table->string('emola_gwtransid', 64)->nullable()->after('emola_message');
            }
            if (! Schema::hasColumn('orders', 'emola_gateway_error')) {
                $table->string('emola_gateway_error', 50)->nullable()->after('emola_gwtransid');
            }
            if (! Schema::hasColumn('orders', 'emola_gateway_description')) {
                $table->string('emola_gateway_description', 255)->nullable()->after('emola_gateway_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'emola_gateway_description',
                'emola_gateway_error',
                'emola_gwtransid',
                'emola_message',
                'emola_error_code',
                'emola_ref_no',
                'emola_request_id',
                'emola_trans_id',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

