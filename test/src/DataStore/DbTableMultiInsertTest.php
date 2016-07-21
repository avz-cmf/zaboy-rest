<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 21.07.16
 * Time: 13:56
 */

namespace zaboy\test\rest\DataStore;


use zaboy\rest\DbSql\MultiInsertSql;
use Zend\Db\TableGateway\TableGateway;

class DbTableMultiInsertTest extends DbTableTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->dbTableName = $this->config['testDbTableMultiInsert']['tableName'];
        $this->adapter = $this->container->get('db');
        $this->object = $this->container->get('testDbTableMultiInsert');
    }

    public function testCreate_multiRow_withoutId()
    {
        $this->_initObject();
        $data = [];
        foreach (range(0, 20000) as $i) {
            $data[] = [
                'fFloat' => 1000.01 + $i,
                'fString' => 'Create_withoutId' . $i,
            ];
        }

        $newItems = $this->object->create($data);
        $this->assertTrue(is_array($newItems));
        $this->assertTrue(count($newItems) === 20001);
        $id = 5;
        foreach ($newItems as $item){
            $this->assertEquals((string)$id,$item[$this->object->getIdentifier()]);
            $id++;
        }
    }

    public function testCreate_multiRow_withId()
    {
        $this->_initObject();
        $data = [];
        foreach (range(5, 20000) as $i) {
            $data[] = [
                $this->object->getIdentifier() => $i,
                'fFloat' => 1000.01 + $i,
                'fString' => 'Create_withoutId' . $i,
            ];
        }

        $newItems = $this->object->create($data);
        $this->assertTrue(is_array($newItems));
        $this->assertEquals(19996, count($newItems));
        $id = 5;
        foreach ($newItems as $item){
            $this->assertEquals((string)$id,$item[$this->object->getIdentifier()]);
            $id++;
        }
    }

    /**
     * This method init $this->object
     */
    protected function _initObject($data = null)
    {

        if (is_null($data)) {
            $data = $this->_itemsArrayDelault;
        }

        $this->_prepareTable($data);
        $sql = new MultiInsertSql($this->adapter, $this->dbTableName);
        $dbTable = new TableGateway($this->dbTableName, $this->adapter, null, null, $sql);

        $dbTable->insert($data);
    }
}
