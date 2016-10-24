<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 19.10.16
 * Time: 17:19
 */
use \zaboy\rest\TableGateway\TableManagerMysql;
use zaboy\rest\DataStore\Eav\SysEntities;
use zaboy\rest\DataStore\Eav\Entity;

return [
    'dataStore' => [
        SysEntities::TABLE_NAME => [
            'class' => SysEntities::class,
            'tableName' => SysEntities::TABLE_NAME
        ],
        SysEntities::ENTITY_PREFIX . 'product' => [
            'class' => Entity::class,
            'tableName' => SysEntities::ENTITY_PREFIX . 'product'
        ],
    ],
];
