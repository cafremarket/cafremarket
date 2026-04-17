<?php

return [
    /*
     * The driver to use to interact with MailChimp API.
     * You may use "log" or "null" to prevent calling the
     * API directly from your environment.
     */
    'driver' => env('NEWSLETTER_DRIVER', Spatie\Newsletter\Drivers\MailChimpDriver::class),
    // 'driver' => env('NEWSLETTER_DRIVER', Spatie\Newsletter\Drivers\MailcoachDriver::class),

    /**
     * These arguments will be given to the driver.
     */
    'driver_arguments' => [
        'api_key' => env('MAILCHIMP_APIKEY'),

        'endpoint' => env('NEWSLETTER_ENDPOINT'),
    ],

    /*
     * The API key of a MailChimp account. You can find yours at
     * https://us10.admin.mailchimp.com/account/api-key-popup/.
     */
    // 'apiKey' => env('MAILCHIMP_APIKEY'),

    /*
     * The list name to use when no list name is specified in a method.
     */
    'default_list_name' => 'subscribers',

    /*
     * The listName to use when no listName has been specified in a method.
     */
    // 'defaultListName' => 'subscribers',

    /*
     * Here you can define properties of the lists.
     */
    'lists' => [
        /*
         * This key is used to identify this list. It can be used
         * as the listName parameter provided in the various methods.
         *
         * You can set it to any string you want and you can add
         * as many lists as you want.
         */
        'subscribers' => [

            /*
             * A MailChimp list id. Check the MailChimp docs if you don't know
             * how to get this value:
             * http://kb.mailchimp.com/lists/managing-subscribers/find-your-list-id.
             */
            'id' => env('MAILCHIMP_LIST_ID'),
        ],
    ],

    /*
     * If you're having trouble with https connections, set this to false.
     */
    'ssl' => false,
];
