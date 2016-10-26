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
 * @see https://docs.google.com/spreadsheets/d/1k51Dtv1z-eK_ic5TXJMdJ9_jeLBtcrKQPQI9A0Jpts0/edit#gid=0
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

    public static $sys_entities = [ "sys_entities" =>
        [
            [ "id" => "11", "entity_type" => "product", "add_date" => "2009-06-04",],
            [ "id" => "12", "entity_type" => "product", "add_date" => "2009-06-05",],
            [ "id" => "13", "entity_type" => "product", "add_date" => "2009-06-06",],
            [ "id" => "14", "entity_type" => "product", "add_date" => "2009-06-07",],
            [ "id" => "15", "entity_type" => "product", "add_date" => "2009-06-08",],
            [ "id" => "16", "entity_type" => "product", "add_date" => "2009-06-09",],
            [ "id" => "31", "entity_type" => "tag", "add_date" => "2009-06-10",],
            [ "id" => "32", "entity_type" => "tag", "add_date" => "2009-06-11",],
            [ "id" => "33", "entity_type" => "tag", "add_date" => "2009-06-12",],
            [ "id" => "21", "entity_type" => "category", "add_date" => "2009-06-13",],
            [ "id" => "22", "entity_type" => "category", "add_date" => "2009-06-14",],
            [ "id" => "23", "entity_type" => "category", "add_date" => "2009-06-15",],
            [ "id" => "24", "entity_type" => "category", "add_date" => "2009-06-16",],
    ]];
    public static $entity_product = [ "entity_product" =>
        [
            [ "id" => "11", "title" => "Edelweiss", "price" => "200",],
            [ "id" => "12", "title" => "Rose", "price" => "50",],
            [ "id" => "13", "title" => "Queen Rose", "price" => "100",],
            [ "id" => "14", "title" => "King Rose", "price" => "100",],
            [ "id" => "15", "title" => "Plate1", "price" => "10",],
            [ "id" => "16", "title" => "Plate2", "price" => "20",],
    ]];
    public static $entity_category = [ "entity_category" =>
        [
            [ "id" => "21", "Name" => "Flowers_",],
            [ "id" => "22", "Name" => "Flowers_Rose_1",],
            [ "id" => "23", "Name" => "Flowers_Rose_2",],
            [ "id" => "24", "Name" => "Utensil",],
    ]];
    public static $entity_tag = [ "entity_tag" =>
        [
            [ "id" => "31", "tag_name" => "HIGH",],
            [ "id" => "32", "tag_name" => "MID",],
            [ "id" => "33", "tag_name" => "LOW",],
    ]];
    public static $prop_product_category = [ "prop_product_category" =>
        [
            [ "id" => "1", "category_id" => "22", "product_id" => "11",],
            [ "id" => "2", "category_id" => "22", "product_id" => "12",],
            [ "id" => "3", "category_id" => "23", "product_id" => "13",],
            [ "id" => "4", "category_id" => "21", "product_id" => "14",],
            [ "id" => "5", "category_id" => "22", "product_id" => "15",],
            [ "id" => "6", "category_id" => "23", "product_id" => "16",],
    ]];
    public static $prop_linked_url = [ "prop_linked_url" =>
        [
            [ "id" => "21", "sys_entities_id" => "11", "url" => "https://www.google.com.ua/?q=Edelweiss", "alt" => "Pot1",],
            [ "id" => "22", "sys_entities_id" => "12", "url" => "https://www.google.com.ua/?q=Rose", "alt" => "Pot2",],
            [ "id" => "23", "sys_entities_id" => "13", "url" => "https://www.google.com.ua/?q=Queen Rose", "alt" => "Plate1",],
            [ "id" => "24", "sys_entities_id" => "24", "url" => "https://www.google.com.ua/?q=Utensil", "alt" => "Utensil",],
    ]];
    public static $prop_tag = [ "prop_tag" =>
        [
            [ "id" => "11", "sys_entities_id" => "11", "tag_id" => "31",],
            [ "id" => "12", "sys_entities_id" => "12", "tag_id" => "31",],
            [ "id" => "13", "sys_entities_id" => "13", "tag_id" => "32",],
            [ "id" => "14", "sys_entities_id" => "22", "tag_id" => "33",],
            [ "id" => "15", "sys_entities_id" => "22", "tag_id" => "33",],
    ]];
    public static $develop_tables_config = [
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
            'product' . SysEntities::ID_SUFFIX => [
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
            'category' . SysEntities::ID_SUFFIX => [
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
            'tag' . SysEntities::ID_SUFFIX => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => self::TAG_TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
        ],
    ];

}
