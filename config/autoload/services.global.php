<?php

return [

    'services' => [
        'invokables' => [
        ],
        'factories' => [
            'TableManagerMysql' => 'zaboy\rest\TableGateway\Factory\TableManagerMysqlFactory'
        ],
        'abstract_factories' => [
            'zaboy\rest\DataStore\Aspect\Factory\AspectDataStoreFactory',
            'zaboy\rest\Middleware\Factory\DataStoreAbstractFactory',
            'zaboy\rest\DataStore\Factory\HttpClientAbstractFactory',
            'zaboy\rest\DataStore\Factory\DbTableAbstractFactory',
            'zaboy\rest\DataStore\Factory\CsvAbstractFactory',
            'zaboy\rest\DataStore\Factory\MemoryAbstractFactory',
            'zaboy\rest\TableGateway\Factory\TableGatewayAbstractFactory',
            'Zend\Db\Adapter\AdapterAbstractServiceFactory',
        ]
    ]
];
