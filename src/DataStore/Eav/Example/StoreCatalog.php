<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Eav\Example;

use zaboy\rest\DataStore\DbTable;
use zaboy\rest\DataStore\Eav\SysEntities;
use zaboy\rest\DataStore\Eav\Entity;
use zaboy\rest\TableGateway\TableManagerMysql as TableManager;

/**
 *
 */
class StoreCatalog
{

    const PRODUCT_RESOURCE_NAME = 'product';
    const PRODUCT_DATASTORE = 'product-datastore';
    const PRODUCT_TABLE_NAME = SysEntities::ENTITY_PREFIX . self::PRODUCT_RESOURCE_NAME;

    public $product_table_config = [
        self::PRODUCT_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => SysEntities::TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
            'title' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
            'price' => [
                TableManager::FIELD_TYPE => 'Decimal',
                TableManager::FIELD_PARAMS => [
                    'nullable' => true,
                    'default' => 0
                ],
            ],
        ],
    ];

}
