<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Elasticsearch Client Configuration
    |--------------------------------------------------------------------------
    |
    | This array will be passed to the Elasticsearch client.
    | See configuration options here:
    |
    | http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_configuration.html
    |
    */

    'config' => [

        'hosts' => [
            'localhost:9200'
        ],

        'retries' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon Elasticsearch Service
    |--------------------------------------------------------------------------
    |
    | Sign all requests made to AWS by simply setting up the config with your
    | AWS settings like the following.
    |
    | 'aws_config' => [
    |    'key' => env('AWS_KEY'),
    |    'secret' => env('AWS_SECRET'),
    |    'region' => env('AWS_REGION'),
    | ],
    |
    */

    'aws_config' => null,

    /*
    |--------------------------------------------------------------------------
    | Index Name
    |--------------------------------------------------------------------------
    |
    | This is the index name that Laravel Hunt will use for all models.
    |
    */

    'index' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Unified Types
    |--------------------------------------------------------------------------
    |
    | Use this to set the default types to search when performing a site
    | wide search.
    |
    */

    'types' => null,

    /*
    |--------------------------------------------------------------------------
    | Fields to Search
    |--------------------------------------------------------------------------
    |
    | This is used to specify which fields to search.
    |
    | 'fields' => ['name^5', 'title^4', 'source'],
    |
    */

    'fields' => null,

    /*
    |--------------------------------------------------------------------------
    | Default Index Settings
    |--------------------------------------------------------------------------
    |
    | This is the settings used when creating an Elasticsearch index.
    |
    | 'settings' => [
    |     'number_of_shards' => 1,
    |     'analysis' => [
    |         'filter' => [
    |             'autocomplete_filter' => [
    |                 'type' => 'edge_ngram',
    |                 'min_gram' => 1,
    |                 'max_gram' => 20,
    |             ],
    |         ],
    |         'analyzer' => [
    |             'autocomplete' => [
    |                 'type' => 'custom',
    |                 'tokenizer' => 'standard',
    |                 'filter' => [
    |                     'lowercase',
    |                     'autocomplete_filter',
    |                 ],
    |             ],
    |         ],
    |     ],
    | ],
    |
    */

    'settings' => null,

    /*
    |--------------------------------------------------------------------------
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | Change this if you use a different model namespace for Laravel.
    |
    */

    'model_namespace' => '\\App\\',

    /*
    |--------------------------------------------------------------------------
    | Multilingual Support
    |--------------------------------------------------------------------------
    |
    | Use this to set support for multiple languages. Basically it suffixes
    | the type with the locale code.
    |
    | For this to work, the model will need to use the `Localized` trait. Or
    | something similar.
    |
    */

    'multilingual' => false,

    /*
    |--------------------------------------------------------------------------
    | Support Locales
    |--------------------------------------------------------------------------
    |
    | This is used in the command line to import and map models. If using the
    | package ``
    |
    */

    'support_locales' => [],
];
