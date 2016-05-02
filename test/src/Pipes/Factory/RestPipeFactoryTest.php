<?php

namespace zaboy\test\rest\Pipes\Factory;

use zaboy\rest\Pipe\Factory\RestRqlFactory;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-01-29 at 18:23:51.
 */
class RestRqlFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var zaboy\rest\Pipe\Factory\RestRqlFactory
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
        $this->object = new RestRqlFactory();
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

    public function testRestRqlFactoryAbstractFactory__canCreateIfConfigAbsent()
    {
        $requestedName = 'resource_name_which_is_unknown';
        //$this->setExpectedException(Exception);
        $result = $this->object->__invoke($this->container, $requestedName);
        $middlewares = $this->object->getMiddlewares();
        $this->assertSame(
                'Closure', get_class($middlewares[300])
        );
    }

    /*
     * @see test.global.php'tablle_with_name_same_as_resource_name'
     */

    public function testRestRqlFactoryAbstractFactory__canCreateIfTableExist()
    {
        $tableName = 'table_with_name_same_as_resource_name';
        $createStatementStr = "CREATE TABLE IF NOT EXISTS $tableName (id INT)";
        $createStatement = $this->adapter->query($createStatementStr);
        $createStatement->execute();
        $result = $this->object->__invoke($this->container, $tableName);
        $this->assertSame(
                true, $result instanceof \Zend\Stratigility\MiddlewareInterface
        );
        $deleteStatementStr = "DROP TABLE IF EXISTS " . $tableName;
        $deleteStatement = $this->adapter->query($deleteStatementStr);
        $deleteStatement->execute();
    }

}
