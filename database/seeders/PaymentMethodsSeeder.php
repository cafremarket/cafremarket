<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentMethodsSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::Now();

        DB::table('payment_methods')->insert([
            'name' => 'PayPal Express Checkout',
            'code' => 'paypal',
            'type' => PaymentMethod::TYPE_PAYPAL,
            'split_money' => false,
            'company_name' => 'PayPal Inc.',
            'website' => 'https://www.paypal.com/',
            'help_doc_link' => 'https://www.paypal.com/us/webapps/mpp/express-checkout',
            'description' => 'Add PayPal as a payment method to allow customers to checkout with PayPal. Express Checkout offers the ease of convenience and security of PayPal, can turn more shoppers into buyers. You must have a PayPal business account to activate this payment method. - You must have a PayPal business account.<br/><strong>To activate PayPal Express: </strong><br/>- You must have a PayPal business account to accept payments.<br/>- Create an app to receive API credentials for testing and live transactions.<br/>- Go to this link to create your app: <small>https://developer.paypal.com/webapps/developer/applications/myapps</small>',
            'admin_description' => 'Add PayPal as a payment method to any checkout with Express Checkout. Express Checkout offers the ease of convenience and security of PayPal, can be set up in minutes and can turn more shoppers into buyers.',
            'admin_help_doc_link' => 'https://developer.paypal.com/docs/integration/direct/express-checkout/integration-jsv4/',
            'enabled' => false,
            'order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('payment_methods')->insert([
            'name' => 'Pagamento na Entrega',
            'code' => 'cod',
            'type' => PaymentMethod::TYPE_MANUAL,
            'split_money' => false,
            'company_name' => 'Pago na Entrega',
            'description' => 'O pagamento na entrega também chamado de "colher na entrega", é a venda de mercadorias por encomenda postal em que o pagamento é feito no momento da entrega, em vez de antecipadamente.',
            'admin_description' => 'O pagamento na entrega também chamado de "colher na entrega", é a venda de mercadorias por encomenda postal em que o pagamento é feito no momento da entrega, em vez de antecipadamente',
            'admin_help_doc_link' => '',
            'enabled' => false,
            'order' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_methods')->insert([
            'name' => 'Transferência bancária',
            'code' => 'wire',
            'type' => PaymentMethod::TYPE_MANUAL,
            'split_money' => false,
            'company_name' => 'Pagar por transferência bancária',
            'description' => 'Pague por transferência bancária, transfira o valor da fatura para a conta do comerciante e confirme manualmente. Após a confirmação do pagamento, os produtos serão enviados..',
            'admin_description' => 'Pague por transferência bancária, transfira o valor da fatura para a conta do comerciante e confirme manualmente. Após a confirmação do pagamento, os produtos serão enviados.',
            'admin_help_doc_link' => '',
            'enabled' => false,
            'order' => 6,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
