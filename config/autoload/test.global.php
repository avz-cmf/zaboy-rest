<?php

return [
    'dataStore' => [
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
        'MiddlewareMemoryTest' => [
            'class' => 'zaboy\rest\Middleware\MiddlewareMemoryStore',
            'dataStore' => 'testMemory'
        ]
    ]/*         * ,
          'services' => [
          'factories' => [
          ],
          'abstract_factories' => [
          'zaboy\rest\DataStore\Factory\DbTableStoresAbstractFactory',
          'zaboy\rest\DataStore\Factory\MemoryStoresAbstractFactory',
          'zaboy\rest\DataStore\Factory\HttpClientStoresAbstractFactory',
          'zaboy\rest\Middleware\Factory\MiddlewareStoreAbstractFactory',
          'zaboy\rest\TableGateway\Factory\TableGatewayAbstractFactory',
          'Zend\Db\Adapter\AdapterAbstractServiceFactory'
          ]
          ] */
];
