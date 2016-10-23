<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\test\rest\DataStore\Eav;

use zaboy\rest\DataStore\DbTable;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;
use Interop\Container\ContainerInterface;
use zaboy\rest\DataStore\Eav\SysEntities;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\Eav\Entity;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-01-11 at 16:19:25.
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Entity
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

    public function test__getEntityName()
    {
        $this->object = $this->container->get(SysEntities::ENTITY_PREFIX . 'product');
        $this->assertEquals('product', $this->object->getEntityName());
    }

    public function test__create()
    {
        $this->object = $this->container->get(SysEntities::ENTITY_PREFIX . 'product');
        $this->object->create([ 'title' => 'title_1', 'price' => 100]);
        $this->assertEquals(1, $this->object->count());
    }

}
