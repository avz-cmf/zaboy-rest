<?php

return [

    'services' => [
        'invokables' => [
        ],
        'factories' => [
        ],
        'abstract_factories' => [
            'zaboy\rest\DataStore\Factory\MemoryAbstractFactory',
            'zaboy\rest\DataStore\Factory\CsvAbstractFactory',
            'zaboy\rest\Middleware\Factory\DataStoreAbstractFactory',
            'zaboy\rest\DataStore\Factory\HttpClientAbstractFactory',
            'zaboy\rest\DataStore\Factory\DbTableAbstractFactory',
            'zaboy\rest\TableGateway\Factory\TableGatewayAbstractFactory',
            'Zend\Db\Adapter\AdapterAbstractServiceFactory'
        ]
    ]
];
