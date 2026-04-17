<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "null"
    |
    */

    'driver' => env('SCOUT_DRIVER', 'algolia'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "algolia"
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
        'index-settings' => [
            // 'users' => [
            //     'searchableAttributes' => ['id', 'name', 'email'],
            //     'attributesForFaceting'=> ['filterOnly(email)'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an open
    | source search engine with minimal configuration. Below, you can state
    | the host and key information for your own Meilisearch installation.
    |
    | See: https://www.meilisearch.com/docs/learn/configuration/instance_options#all-instance-options
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            // 'users' => [
            //     'filterableAttributes'=> ['id', 'name', 'email'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Typesense settings. Typesense is an open
    | source search engine using minimal configuration. Below, you will
    | state the host, key, and schema configuration for the instance.
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
            'nodes' => [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', '8108'),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ],
            ],
            'nearest_node' => [
                'host' => env('TYPESENSE_HOST', 'localhost'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
            'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
            'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
            'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
            'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
        ],
        // 'max_total_results' => env('TYPESENSE_MAX_TOTAL_RESULTS', 1000),
        'model-settings' => [
            Inventory::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string', 'index' => true],
                        ['name' => 'brand', 'type' => 'string', 'facet' => true],
                        ['name' => 'description', 'type' => 'string'],
                        ['name' => 'product_gtin', 'type' => 'string'],
                        ['name' => 'sku', 'type' => 'string'],
                        ['name' => 'created_at', 'type' => 'int64'],
                        // ['name' => '__soft_deleted', 'type' => 'int32', 'optional' => true],
                    ],
                    'default_sorting_field' => 'created_at', // Or another appropriate field
                ],
                'search-parameters' => [
                    'query_by' => 'title,product_gtin,brand',  // Fields to query against
                ],
            ],
            Product::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string', 'sort' => true],
                        ['name' => 'name', 'type' => 'string', 'index' => true, 'sort' => true],
                        ['name' => 'model_number', 'type' => 'string', 'facet' => true],
                        ['name' => 'gtin', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string'],
                        ['name' => 'manufacturer', 'type' => 'string'],
                        ['name' => 'created_at', 'type' => 'int64'],
                        // ['name' => '__soft_deleted', 'type' => 'int32', 'optional' => true],
                    ],
                    'default_sorting_field' => 'name', // Or another appropriate field
                ],
                'search-parameters' => [
                    'query_by' => 'name,gtin,manufacturer',  // Fields to query against
                ],
            ],
            Category::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string', 'sort' => true],
                        ['name' => 'name', 'type' => 'string', 'index' => true, 'sort' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        // ['name' => '__soft_deleted', 'type' => 'int32', 'optional' => true],
                    ],
                    'default_sorting_field' => 'name',
                ],
                'search-parameters' => [
                    'query_by' => 'name',  // Fields to query against
                ],
            ],
            Customer::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name', 'type' => 'string', 'facet' => true, 'sort' => true],
                        ['name' => 'email', 'type' => 'string', 'index' => true],
                        ['name' => 'active', 'type' => 'bool', 'filterable' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'name', // Or another appropriate field
                ],
                'search-parameters' => [
                    'query_by' => 'name,email',  // Fields to query against
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your MySQL settings.
    |
    */

    'mysql' => [
        'mode' => 'NATURAL_LANGUAGE',
        'model_directories' => [app_path('Models')],
        'min_search_length' => 3,
        'min_fulltext_search_length' => 4,
        'min_fulltext_search_fallback' => 'LIKE',
        'query_expansion' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | TntSearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your TntSearch settings.
    |
    */

    'tntsearch' => [
        'storage' => storage_path(), // Place where the index files will be stored
        'fuzziness' => env('TNTSEARCH_FUZZINESS', false),
        'fuzzy' => [
            'prefix_length' => 2,
            'max_expansions' => 50,
            'distance' => 2,
        ],
        // When true, queries like "le" match "Lenovo" (prefix / as-you-type). Required for short mobile searches.
        'asYouType' => env('TNTSEARCH_AS_YOU_TYPE', true),
        'searchBoolean' => env('TNTSEARCH_BOOLEAN', false),
    ],
];
