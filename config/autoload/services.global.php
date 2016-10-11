<?php

return [

    'services' => [
        'invokables' => [
        ],
        'factories' => [
            'TableManagerMysql' => 'zaboy\rest\TableGateway\Factory\TableManagerMysqlFactory'
        ],
        'abstract_factories' => [
            'zaboy\rest\DataStore\Aspect\Factory\AspectAbstractFactory',
            'zaboy\rest\Middleware\Factory\DataStoreAbstractFactory',
            'zaboy\rest\DataStore\Factory\HttpClientAbstractFactory',
            'zaboy\rest\DataStore\Factory\DbTableAbstractFactory',
            'zaboy\rest\DataStore\Factory\CsvAbstractFactory',
            'zaboy\rest\DataStore\Factory\MemoryAbstractFactory',
            'zaboy\rest\DataStore\Factory\CacheableAbstractFactory',
            'Zend\Db\Adapter\AdapterAbstractServiceFactory',
            'zaboy\rest\TableGateway\Factory\TableGatewayAbstractFactory',
        ]
    ]
];
