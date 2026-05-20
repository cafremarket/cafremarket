<?php

/**
 * eMola / Movitel USSD Push API — see MOVITEL USSD PUSH API SPECIFICATION v1.5.
 */
return [
    'wsdl' => env('EMOLA_WSDL', 'http://10.229.16.29:8520/BCCSGateway/BCCSGateway?wsdl'),
    'endpoint' => env('EMOLA_ENDPOINT', 'http://10.229.16.29:8520/BCCSGateway'),
    'username' => env('EMOLA_USERNAME'),
    'password' => env('EMOLA_PASSWORD'),
    'partner_code' => env('EMOLA_PARTNER_CODE'),
    'key' => env('EMOLA_KEY'),
    'language' => env('EMOLA_LANGUAGE', 'pt'),
    // Register this exact URL with Movitel (must match your live domain + HTTPS).
    'callback_url' => env('EMOLA_CALLBACK_URL', 'https://cafremarket.co.mz/api/emola/callback'),
    'callback_url_alt' => env('EMOLA_CALLBACK_URL_ALT', 'https://cafremarket.co.mz/payment/callback/emola'),
    'timeout_seconds' => (int) env('EMOLA_TIMEOUT_SECONDS', 60),
    'soap_namespace' => 'http://webservice.bccsgw.viettel.com/',
    'fake' => env('APP_ENV') === 'production'
        ? false
        : (bool) env('EMOLA_FAKE', false),

    // Movitel wscode values (spec §B).
    'wscode' => [
        'push' => 'pushUssdMessage',
        'query' => 'pushUssdQueryTrans',
        'b2c' => 'pushUssdDisbursementB2C',
        'beneficiary' => 'queryBeneficiaryName',
        'balance' => 'queryAccountBalance',
    ],

    // transType for pushUssdQueryTrans (spec §B.2).
    'trans_types' => [
        'c2b' => 'C2B',
        'b2c' => 'B2C',
        'query_txn' => 'QUERY_TXN',
        'query_cus' => 'QUERY_CUS',
        'query_ben' => 'QUERY_BEN',
    ],

    // Field limits (spec §B) + Movitel merchant caps (MZN / MT).
    'limits' => [
        'msisdn' => 9,
        'trans_id_min' => 15,
        'trans_id_max' => 30,
        'trans_amount_min' => (int) env('EMOLA_MIN_TRANS_AMOUNT', 1),
        // USSD transAmount field: spec §B.1 allows up to 5 digits (max single push 99,999).
        'trans_amount_digits' => 5,
        // Order / checkout payments (C2B).
        'order_transaction_max' => (int) env('EMOLA_ORDER_TRANSACTION_MAX', 50_000),
        // Wallet top-up deposits.
        'deposit_transaction_max' => (int) env('EMOLA_DEPOSIT_TRANSACTION_MAX', 1_000),
        // Total paid per customer MSISDN per calendar day (orders + deposits).
        'customer_daily_max' => (int) env('EMOLA_CUSTOMER_DAILY_MAX', 500_000),
        // @deprecated Use order_transaction_max — kept for backward compatibility.
        'trans_amount_max' => (int) env('EMOLA_MAX_TRANS_AMOUNT', 50_000),
        'ref_no_max' => 20,
        'sms_content_max' => 180,
        'partner_code_max' => 30,
    ],

    // Gateway Result.error codes (spec §A.2).
    'gateway_errors' => [
        '0' => 'Success when calling detail API',
        '1000' => 'Username is invalid',
        '1001' => 'Username does not exist',
        '1002' => 'Username is inactive',
        '2000' => 'Webservice code is invalid',
        '2001' => 'Webservice does not exist',
        '2002' => 'Webservice is inactive',
        '2003' => 'Connection to the web service has problems',
        '2004' => 'Could not connect to the business web service',
        '2005' => 'Webservice error',
        '2007' => 'Timeout when calling detail API',
        '3000' => 'Client was not found or inactive',
        '4000' => 'Request message format is not accurate',
        '4001' => 'Requested web service does not exist',
        '5000' => 'Too many requests',
        '6000' => 'System overloading',
        '6001' => 'No right to access the web service',
        '6002' => 'Calling right expired',
        '6003' => 'Login failed — check username, password and IP',
        '6004' => 'Login failed — invalid username',
        '6005' => 'Error when load response to DOM',
        '7000' => 'Database connection problem',
        '7001' => 'Database error',
        '8000' => 'Incorrect IP',
        '9001' => 'Exception',
        '9002' => 'Error when prepare soap input',
    ],

    // Business errorCode in Result.original CDATA (spec §C).
    'business_errors' => [
        '0' => 'Successfully',
        '02' => 'Login failed — IP not valid',
        '03' => 'Transaction failed',
        '05' => 'Invalid partner code',
        '06' => 'Invalid amount',
        '07' => 'Invalid MSISDN',
        '08' => 'Invalid SMS content length',
        '09' => 'Transaction ID too long',
        '10' => 'ISDN not in whitelist',
        '11' => 'Customer did not enter PIN',
        '12' => 'Customer does not have eMola account',
        '14' => 'Transaction ID already exists',
        '20' => 'MSISDN is in another process',
        '22' => 'Push message done (async mode)',
        '23' => 'Invalid refNo',
        '28' => 'Transaction has already been processed',
        '98' => 'Login failed — invalid user',
        '99' => 'Error while processing',
    ],
];
