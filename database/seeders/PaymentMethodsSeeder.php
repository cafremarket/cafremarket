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
            'order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_methods')->insert([
            'name' => 'Stripe',
            'code' => 'stripe',
            'type' => PaymentMethod::TYPE_CREDIT_CARD,
            'split_money' => true,
            'company_name' => 'Stripe Inc.',
            'website' => 'https://stripe.com/',
            'help_doc_link' => 'https://stripe.com/docs/checkout/tutorial',
            'description' => 'Stripe is one of the best and safe option to charge credit and debit cards around the world. Stripe has a simple fee structure and no hidden costs. No other gateway or merchant account is required. <br/><strong>By using Stripe: </strong><br/>- You have to connect our platform to your Stripe account. <br/>- You agree to Stripe\'s <a href="https://stripe.com/us/privacy" target="_blank">Terms of Service</a>.',
            'admin_description' => 'Stripe is one of the best and safe option to charge credit and debit cards around the world. Stripe has a product for marketplace like this. To enable Stripe to your vendors, you must have to register your platform with Stripe.<br/><strong> Follow This Simple steps:</strong><br/>- Create an Stripe application using the bellow information. <a href="https://stripe.com/docs/connect/quickstart" target="_blank">Check their documentation for help.</a><br/>- Update the .env file on your server with Stripe API credentials.<br/><br/><strong>Remember </strong> when you register your platform use this information: <br/>- Name: \''.get_platform_title().'\'<br/>- Website URL: \''.route('homepage').'\'<br/>- Redirect URL: \''.route('admin.setting.stripe.redirect').'\'',
            'admin_help_doc_link' => 'https://stripe.com/docs/connect/quickstart',
            'order' => 2,
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
            'order' => 6,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
