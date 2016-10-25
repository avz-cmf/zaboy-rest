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
 *
 * @see https://docs.google.com/spreadsheets/d/1k51Dtv1z-eK_ic5TXJMdJ9_jeLBtcrKQPQI9A0Jpts0/edit
 */
class StoreCatalog
{

    //'entity_productl'
    const PRODUCT_TABLE_NAME = SysEntities::ENTITY_PREFIX . 'product';
    //'entity_category'
    const CATEGORY_TABLE_NAME = SysEntities::ENTITY_PREFIX . 'category';
    //'entity_tag'
    const TAG_TABLE_NAME = SysEntities::ENTITY_PREFIX . 'tag';
    //'prop_linked_url'
    const PROP_LINKED_URL_TABLE_NAME = SysEntities::PROP_PREFIX . 'linked_url';
    //'prop_product_category'
    const PROP_PRODUCT_CATEGORY_TABLE_NAME = SysEntities::PROP_PREFIX . 'product_category';
    //'prop_tag'
    const PROP_TAG_TABLE_NAME = SysEntities::PROP_PREFIX . 'tag';

    public $develop_tables_config = [
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
        self::CATEGORY_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => 'sys_entities',
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
            'name' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
        ],
        self::TAG_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => 'sys_entities',
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
            'tag_name' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
        ],
        self::PROP_LINKED_URL_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
                ]
            ],
            SysEntities::TABLE_NAME . SysEntities::ID_SUFFIX => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => SysEntities::TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
            'url' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
            'alt' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
        ],
        self::PROP_PRODUCT_CATEGORY_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
                ]
            ],
            SysEntities::TABLE_NAME . SysEntities::ID_SUFFIX => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => SysEntities::TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
            self::PRODUCT_TABLE_NAME . SysEntities::ID_SUFFIX => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => self::PRODUCT_TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ],
                TableManager::UNIQUE_KEY => true
            ],
            self::CATEGORY_TABLE_NAME . SysEntities::ID_SUFFIX => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => self::CATEGORY_TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
        ],
        self::PROP_TAG_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
                ]
            ],
            SysEntities::TABLE_NAME . SysEntities::ID_SUFFIX => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => SysEntities::TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
            self::TAG_TABLE_NAME . SysEntities::ID_SUFFIX => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => self::PRODUCT_TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
        ],
    ];

}
