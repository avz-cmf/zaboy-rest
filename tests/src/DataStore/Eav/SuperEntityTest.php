<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\test\rest\DataStore\Eav;

use Interop\Container\ContainerInterface;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\Eav\Example\StoreCatalog;
use zaboy\rest\DataStore\Eav\SuperEntity;
use zaboy\rest\DataStore\Eav\SysEntities;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-01-11 at 16:19:25.
 */
class SuperEntityTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SuperEntity
     */
    protected $object;

    /** @var  ContainerInterface */
    protected $container;

    protected function setUp()
    {
        $this->container = include 'config/container.php';
        $sysEntities = $this->container->get(SysEntities::TABLE_NAME);
        $sysEntities->deleteAll();
    }


    public function provider_supertEntityGetSql()
    {
        $query1 = new Query();
        $query2 = new Query();
        $query2->setQuery(
            new AndNode([
                new LtNode('price', 23),
                new NeNode('icon', 'icon1.jpg'),
            ])
        );
        $query3 = new Query();
        $query3->setSelect(new SelectNode(['price', 'icon']));
        $query4 = new Query();
        $query4->setSort(new SortNode(['price' => -1, 'icon' => +1]));
        /** @noinspection SqlNoDataSourceInspection */
        return array(
            array(
                'SELECT `sys_entities`.*, `entity_product`.*, `entity_mainicon`.* FROM `sys_entities` ' .
                'INNER JOIN `entity_product` ON `entity_product`.`id`=`sys_entities`.`id` ' .
                'INNER JOIN `entity_mainicon` ON `entity_mainicon`.`id`=`entity_product`.`id` ' .
                'WHERE \'1\' = \'1\' ORDER BY `sys_entities`.`id` ASC',
                $query1
            ),
            array(
                'SELECT `sys_entities`.*, `entity_product`.*, `entity_mainicon`.* FROM `sys_entities` ' .
                'INNER JOIN `entity_product` ON `entity_product`.`id`=`sys_entities`.`id` ' .
                'INNER JOIN `entity_mainicon` ON `entity_mainicon`.`id`=`entity_product`.`id` ' .
                'WHERE ((`price`<\'23\') AND (`icon`<>\'icon1.jpg\')) ORDER BY `sys_entities`.`id` ASC',
                $query2
            ),
            array(
                'SELECT `entity_product`.`price` AS `price`, `entity_mainicon`.`icon` AS `icon` FROM `sys_entities` ' .
                'INNER JOIN `entity_product` ON `entity_product`.`id`=`sys_entities`.`id` ' .
                'INNER JOIN `entity_mainicon` ON `entity_mainicon`.`id`=`entity_product`.`id` ' .
                'WHERE \'1\' = \'1\' ORDER BY `sys_entities`.`id` ASC',
                $query3
            ),
            array(
                'SELECT `sys_entities`.*, `entity_product`.*, `entity_mainicon`.* FROM `sys_entities` ' .
                'INNER JOIN `entity_product` ON `entity_product`.`id`=`sys_entities`.`id` ' .
                'INNER JOIN `entity_mainicon` ON `entity_mainicon`.`id`=`entity_product`.`id` ' .
                'WHERE \'1\' = \'1\' ORDER BY `entity_product`.`price` DESC, `entity_mainicon`.`icon` ASC',
                $query4
            )
        );
    }

    /**
     * @dataProvider  provider_supertEntityGetSql
     * @param $sql
     * @param Query $query
     */
    public function test__supertEntityGetSql($sql, Query $query)
    {
        $this->object = $this->container->get(StoreCatalog::PRODUCT_TABLE_NAME . SuperEntity::INNER_JOIN . StoreCatalog::MAINICON_TABLE_NAME);
        $this->assertEquals($sql, $this->object->getSqlQuery($query));
    }


    public function provider_query()
    {
        $emptyQuery = new Query();
        $queryWithAndNode = new Query();
        $queryWithAndNode->setQuery(
            new AndNode([
                new LtNode('price', 23),
                new NeNode('icon', 'icon1.jpg'),
            ])
        );
        $queryWithSelect = new Query();
        $queryWithSelect->setSelect(new SelectNode(['price', 'icon']));
        $queryWithSort = new Query();
        $queryWithSort->setSort(new SortNode(['price' => -1, 'icon' => +1]));
        $queryWithSelectProps = new Query();
        $queryWithSelectProps->setSelect(new SelectNode([StoreCatalog::PROP_LINKED_URL_TABLE_NAME]));
        return array(
            array(
                $emptyQuery,
                array(
                    [
                        'title' => 'Plate41-mainicon',
                        'price' => '21',
                        'icon' => 'icon1.jpg'],
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg'],
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '23',
                        'icon' => 'icon3.jpg']
                ),
                array(
                    [
                        'title' => 'Plate41-mainicon',
                        'price' => '21',
                        'icon' => 'icon1.jpg'],
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg'],
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '23',
                        'icon' => 'icon3.jpg']
                )
            ),
            array(
                $queryWithAndNode,
                array(
                    [
                        'title' => 'Plate41-mainicon',
                        'price' => '21',
                        'icon' => 'icon1.jpg'],
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg'],
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '23',
                        'icon' => 'icon3.jpg']
                ),
                array(
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg'],
                )
            ),
            array(
                $queryWithSelect,
                array(
                    [
                        'title' => 'Plate41-mainicon',
                        'price' => '21',
                        'icon' => 'icon1.jpg'],
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg'],
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '23',
                        'icon' => 'icon3.jpg']
                ),
                array(
                    [
                        'price' => '21',
                        'icon' => 'icon1.jpg'],
                    [
                        'price' => '22',
                        'icon' => 'icon2.jpg'],
                    [
                        'price' => '23',
                        'icon' => 'icon3.jpg']
                ),
            ),
            array(
                $queryWithSort,
                array(
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '23',
                        'icon' => 'icon6.jpg'],
                    [
                        'title' => 'Plate41-mainicon',
                        'price' => '21',
                        'icon' => 'icon1.jpg'],
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg'],
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '22',
                        'icon' => 'icon3.jpg'],
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '23',
                        'icon' => 'icon5.jpg']
                ),
                array(
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '23',
                        'icon' => 'icon5.jpg'],
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '23',
                        'icon' => 'icon6.jpg'],
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg'],
                    [
                        'title' => 'Plate43-mainicon',
                        'price' => '22',
                        'icon' => 'icon3.jpg'],
                    [
                        'title' => 'Plate41-mainicon',
                        'price' => '21',
                        'icon' => 'icon1.jpg'],
                ),
            ),
            array(
                $queryWithSelectProps,
                array(
                    [
                        'title' => 'Plate41-mainicon',
                        'price' => '21',
                        'icon' => 'icon1.jpg',
                        StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                            ['url' => 'http://google.com', 'alt' => 'Pot1'],
                            ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                        ]
                    ],
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg',
                        StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                            ['url' => 'http://google.com2', 'alt' => 'Pot3'],
                            ['url' => 'http://google.com3', 'alt' => 'Pot4'],
                        ]
                    ],
                ),
                array(
                    [
                        'title' => 'Plate41-mainicon',
                        'price' => '21',
                        'icon' => 'icon1.jpg',
                        StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                            ['url' => 'http://google.com', 'alt' => 'Pot1'],
                            ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                        ]
                    ],
                    [
                        'title' => 'Plate42-mainicon',
                        'price' => '22',
                        'icon' => 'icon2.jpg',
                        StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                            ['url' => 'http://google.com2', 'alt' => 'Pot3'],
                            ['url' => 'http://google.com3', 'alt' => 'Pot4'],
                        ]
                    ],
                ),
            )
        );
    }

    /**
     * @dataProvider provider_query
     * @param Query $query
     * @param array $created
     * @param array $expectedResult
     */
    public function test_query(Query $query, array $created, array $expectedResult)
    {

        $this->object = $this->container->get(StoreCatalog::PRODUCT_TABLE_NAME . SuperEntity::INNER_JOIN . StoreCatalog::MAINICON_TABLE_NAME);
        foreach ($created as $item) {
            $this->object->create($item);
        }
        $result = $this->object->query($query);
            
        foreach ($result as &$item) {
            //Deleting fields whose values can not be defined
            $unset = array_diff(array_keys($item), array_keys($created[0]));
            foreach ($unset as $key) {
                unset($item[$key]);
            }
            //Deleting invested fields whose values can not be defined
            if (isset($item[StoreCatalog::PROP_LINKED_URL_TABLE_NAME])) {
                foreach ($item[StoreCatalog::PROP_LINKED_URL_TABLE_NAME] as &$propItem) {
                    $propUnset = array_diff(
                        array_keys($propItem),
                        array_keys($expectedResult[0][StoreCatalog::PROP_LINKED_URL_TABLE_NAME][0])
                    );
                    foreach ($propUnset as $propKey) {
                        unset($propItem[$propKey]);
                    }
                }
            }
        }
        $this->assertEquals($expectedResult, $result);
    }

    public function provider_create()
    {
        if (!$this->container) {
            $this->container = include 'config/container.php';
        }
        return array(
            //create item
            array(
                $this->container->get(StoreCatalog::PRODUCT_TABLE_NAME . SuperEntity::INNER_JOIN . StoreCatalog::MAINICON_TABLE_NAME),
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg'
                ],
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg'
                ]
            ),
            //create item with props
            array(
                $this->container->get(StoreCatalog::PRODUCT_TABLE_NAME . SuperEntity::INNER_JOIN . StoreCatalog::MAINICON_TABLE_NAME),
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        ['url' => 'http://google.com', 'alt' => 'Pot1'],
                        ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                    ]
                ],
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        ['url' => 'http://google.com', 'alt' => 'Pot1'],
                        ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                    ]
                ]
            )
        );
    }

    /**
     * @dataProvider provider_create
     * @param $obj
     * @param array $createdItem
     * @param array $expectedResult
     */
    public function test_createEntity($obj, array $createdItem, array $expectedResult)
    {
        $this->object = $obj;
        $newItem = $this->object->create($createdItem);

        //Deleting fields whose values can not be defined
        $unset = array_diff(array_keys($newItem), array_keys($expectedResult));
        foreach ($unset as $key) {
            unset($newItem[$key]);
        }
        //Deleting invested fields whose values can not be defined
        if (isset($newItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME])) {
            foreach ($newItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME] as &$propItem) {
                $propUnset = array_diff(
                    array_keys($propItem),
                    array_keys($expectedResult[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][0])
                );
                foreach ($propUnset as $propKey) {
                    unset($propItem[$propKey]);
                }
            }
        }
        $this->assertEquals($expectedResult, $newItem);

    }

    public function provider_update()
    {
        if (!$this->container) {
            $this->container = include 'config/container.php';
        }
        return array(
            //update item
            array(
                $this->container->get(StoreCatalog::PRODUCT_TABLE_NAME . SuperEntity::INNER_JOIN . StoreCatalog::MAINICON_TABLE_NAME),
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg'
                ],
                [
                    'price' => '25',
                ],
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '25',
                    'icon' => 'icon4.jpg'
                ]
            ),
            //add new prop in item
            array(
                $this->container->get(StoreCatalog::PRODUCT_TABLE_NAME . SuperEntity::INNER_JOIN . StoreCatalog::MAINICON_TABLE_NAME),
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        ['url' => 'http://google.com', 'alt' => 'Pot1'],
                        ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                    ]
                ],
                [
                    'price' => '25',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        [],
                        [],
                        ['url' => 'http://google.com2', 'alt' => 'Pot3'],
                    ]
                ],
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '25',
                    'icon' => 'icon4.jpg',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        ['url' => 'http://google.com', 'alt' => 'Pot1'],
                        ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                        ['url' => 'http://google.com2', 'alt' => 'Pot3'],
                    ]
                ]
            ),
            //update prop in item
            array(
                $this->container->get(StoreCatalog::PRODUCT_TABLE_NAME . SuperEntity::INNER_JOIN . StoreCatalog::MAINICON_TABLE_NAME),
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        ['url' => 'http://google.com', 'alt' => 'Pot1'],
                        ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                    ]
                ],
                [
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        [],
                        ['url' => 'http://google.com2'],
                    ]
                ],
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        ['url' => 'http://google.com', 'alt' => 'Pot1'],
                        ['url' => 'http://google.com2', 'alt' => 'Pot2'],
                    ]
                ]
            ),
            //remove prop in item
            array(
                $this->container->get(StoreCatalog::PRODUCT_TABLE_NAME . SuperEntity::INNER_JOIN . StoreCatalog::MAINICON_TABLE_NAME),
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        ['url' => 'http://google.com', 'alt' => 'Pot1'],
                        ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                    ]
                ],
                [
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        [],
                    ]
                ],
                [
                    'title' => 'Plate4-mainicon',
                    'price' => '24',
                    'icon' => 'icon4.jpg',
                    StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                        ['url' => 'http://google.com', 'alt' => 'Pot1'],
                    ]
                ]
            )
        );
    }

    /**
     * @dataProvider provider_update
     * @param $obj
     * @param array $createdItem
     * @param array $updateItem
     * @param array $expectedResult
     */
    public function test_updateEntity($obj, array $createdItem, array $updateItem, array $expectedResult)
    {
        $this->object = $obj;
        $newCreatedItem = $this->object->create($createdItem);

        //Add the id in the field who want to upgrade.
        $updateItem['id'] = $newCreatedItem['id'];
        if (isset($updateItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME])) {
            for ($i = 0; $i < count($updateItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME]); ++$i) {
                if (!isset($newCreatedItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][$i])){
                    break;
                }
                if (!isset($updateItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][$i]['id'])) {
                    $updateItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][$i]['id'] =
                        $newCreatedItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][$i]['id'];
                }
            }
        }

        $newUpdateItem = $this->object->update($updateItem);
        $unset = array_diff(array_keys($newUpdateItem), array_keys($expectedResult));

        //Deleting fields whose values can not be defined
        foreach ($unset as $key) {
            unset($newUpdateItem[$key]);
        }

        //Deleting invested fields whose values can not be defined
        if (isset($newUpdateItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME])) {
            foreach ($newUpdateItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME] as &$propItem) {
                $propUnset = array_diff(
                    array_keys($propItem),
                    array_keys($expectedResult[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][0])
                );
                foreach ($propUnset as $propKey) {
                    unset($propItem[$propKey]);
                }
            }
        }

        $this->assertEquals($expectedResult, $newUpdateItem);
    }
}









































