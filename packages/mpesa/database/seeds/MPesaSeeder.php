<?php

namespace Incevio\Package\MPesa\Database\Seeds;

use Carbon\Carbon;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MPesaSeeder extends Seeder
{
    public function run()
    {
        if (DB::table('payment_methods')->where('code', 'mpesa')->first()) {
            return;
        }

        DB::table('payment_methods')->insert([
            'name' => 'M-Pesa',
            'code' => 'mpesa',
            'type' => PaymentMethod::TYPE_OTHERS,
            'split_money' => false,
            'company_name' => 'mpesa',
            'website' => 'https://www.vm.co.mz/m-pesa',
            'help_doc_link' => 'https://www.vm.co.mz/m-pesa',
            'description' => 'Aceite pagamentos com o pacote de pagamentos M-Pesa. <br/><strong>Ao utilizar o mpesa: </strong><br/>- Concorda com os termos do M-Pesa. <a href="https://www.vm.co.mz/m-pesa" target="_blank">Termos de Serviço</a>.',
            'admin_description' => 'Aceite pagamentos com o pacote de pagamentos M-Pesa.',
            'admin_help_doc_link' => 'https://developer.mpesa.vm.co.mz/Documentation',
            'order' => 1,
            'enabled' => false,
            'created_at' => Carbon::Now(),
            'updated_at' => Carbon::Now(),
        ]);
    }
}
