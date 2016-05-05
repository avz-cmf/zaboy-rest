<?php

return [

    'services' => [
        'invokables' => [
        ],
        'factories' => [
        ],
        'abstract_factories' => [
            'zaboy\rest\Middleware\Factory\DataStoreAbstractFactory',
            'zaboy\rest\DataStore\Factory\HttpClientAbstractFactory',
            'zaboy\rest\DataStore\Factory\DbTableAbstractFactory',
            'zaboy\rest\DataStore\Factory\MemoryAbstractFactory',
            'zaboy\rest\Queue\DataStore\Factory\QueuesAbstractFactory',
            'zaboy\rest\TableGateway\Factory\TableGatewayAbstractFactory',
            'Zend\Db\Adapter\AdapterAbstractServiceFactory',
            'zaboy\rest\Queue\Factory\QueueClientAbstracFactory',
        ]
    ]
];
