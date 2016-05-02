<?php

namespace zaboy\test\DataStore\Factory;

use zaboy\rest\DataStore;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-01-29 at 18:23:51.
 */
class DbTableAbstractFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Returner
     */
    protected $object;

    /**
     * @var Zend\Db\Adapter\Adapter
     */
    protected $adapter;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->container = include './config/container.php';
        $this->object = new DataStore\Factory\DbTableAbstractFactory();
        $this->adapter = $this->container->get('db');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /*
     * @see test.global.php
     */

    public function testDbTableAbstractFactory__canCreateIfConfigAbsent()
    {
        $requestedName = 'the_name_which_has_not_config';
        $result = $this->object->canCreate($this->container, $requestedName);
        $this->assertSame(
                false, $result
        );
    }

    /*
     * @see test.global.php
     */

    public function testDbTableAbstractFactory__canCreateIfConfigExist()
    {
        $createStatementStr = 'CREATE TEMPORARY TABLE IF NOT EXISTS test_res_tablle (id INT)';
        $createStatement = $this->adapter->query($createStatementStr);
        $createStatement->execute();

        $container = include 'config/container.php';
        $requestedName = 'testDbTable';
        $result = $this->object->canCreate($container, $requestedName);
        $this->assertSame(
                true, $result
        );
    }

    /*
     * @see test.global.php
     */

    public function testDbTableAbstractFactory__invokeIfConfigExist()
    {
        $createStatementStr = 'CREATE TEMPORARY TABLE IF NOT EXISTS test_res_tablle (id INT)';
        $createStatement = $this->adapter->query($createStatementStr);
        $createStatement->execute();

        $container = include 'config/container.php';
        $requestedName = 'testDbTable';
        $result = $this->object->__invoke($container, $requestedName);
        $this->assertSame(
                'zaboy\rest\DataStore\DbTable', get_class($result)
        );
    }

}
