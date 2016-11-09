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
];
