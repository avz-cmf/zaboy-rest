<?php

return [
    'dataStore' => [
        'test_DataStoreDbTableWithNameAsResourceName' => [
            'class' => 'zaboy\rest\DataStore\DbTable',
            'tableName' => 'table_for_db_data_store'
        ],
        'test_StoreForMiddleware' => [
            'class' => 'zaboy\rest\DataStore\Memory',
        ],
        'testDbTable' => [
            'class' => 'zaboy\rest\DataStore\DbTable',
            'tableName' => 'test_res_tablle'
        ],
        'testHttpClient' => [
            'class' => 'zaboy\rest\DataStore\HttpClient',
            'tableName' => 'test_res_http',
            'url' => 'http://zaboy-rest.loc/api/rest/test_res_http',
            'options' => ['timeout' => 30]
        ],
        'testMemory' => [
            'class' => 'zaboy\rest\DataStore\Memory',
        ]
    ],
    'middleware' => [
        'test_MiddlewareWithNameAsResourceName' => [
            'class' => 'zaboy\rest\Middleware\DataStoreRest',
            'dataStore' => 'test_StoreForMiddleware'
        ],
        'MiddlewareMemoryTest' => [
            'class' => 'zaboy\rest\Examples\Middleware\DataStoreMemory',
            'dataStore' => 'testMemory'
        ]
    ]
];
