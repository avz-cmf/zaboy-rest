<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:04 PM
 */

namespace zaboy\rest\DataStore\Composite\Example;

use zaboy\rest\TableGateway\TableManagerMysql as TableManager;

class Store
{
    //'roduct'
    const PRODUCT_TABLE_NAME = 'product';
    //'category'
    const IMAGE_TABLE_NAME = 'images';

    public static $product = ["product" =>
        [
            ["id" => "11", "title" => "Edelweiss", "price" => "200"],
            ["id" => "12", "title" => "Rose", "price" => "50"],
            ["id" => "13", "title" => "Queen Rose", "price" => "100"],
            ["id" => "14", "title" => "King Rose", "price" => "100"],
        ]];
    public static $images = ["images" =>
        [
            ["id" => "21", "image" => "icon1.jpg", "product_id" => "11"],
            ["id" => "22", "image" => "icon2.jpg", "product_id" => "12"],
            ["id" => "23", "image" => "icon3.jpg", "product_id" => "11"],
            ["id" => "24", "image" => "icon4.jpg", "product_id" => "13"],
            ["id" => "26", "image" => "icon5.jpg", "product_id" => "14"],
            ["id" => "27", "image" => "icon6.jpg", "product_id" => "12"],
            ["id" => "28", "image" => "icon7.jpg", "product_id" => "14"],
            ["id" => "29", "image" => "icon8.jpg", "product_id" => "14"],
        ]];

    public static $develop_tables_config = [
        self::PRODUCT_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
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
        self::IMAGE_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
                ],
            ],
            'image' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
            'product_id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => Store::PRODUCT_TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ]
        ],

    ];
}