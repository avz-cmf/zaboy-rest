<?php

namespace zaboy\test\rest\Queue\Adapter;

use zaboy\rest\DataStore\Interfaces\DataStoresInterface;
use zaboy\rest\RestException;
use zaboy\rest\Queue\Adapter\DataStores;
use ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use zaboy\rest\DataStore\Memory;
use Xiag\Rql\Parser\Query;

class DataStoresTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DbTable
     */
    protected $object;

    /**
     *
     * @var array
     */
    protected $_queuesList = array(
        'firstQueue10',
        'nextQueue21',
        'nextQueue22',
        'lastQueue32'
    );

    /**
     *
     * @var array
     */
    protected $_messageList;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $queuesDataStore = new Memory();
        $messagesDataStore = new Memory();
        $this->object = new DataStores($queuesDataStore, $messagesDataStore);

        $date = new \DateTime('@1419237113');
        $this->_messageList = array(
            12,
            12.12,
            'string12',
            [22, 22.22, 'string22'],
            $date
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    public function test_getPriorityHandler()
    {
        $this->assertTrue(
                $this->object->getPriorityHandler()
                instanceof
                \ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface
        );
    }

    public function test_createQueue()
    {
        $this->assertEquals(
                $this->object
                , $this->object->createQueue('Queue_Name_1')
        );
        $queues = $this->object->listQueues();
        $this->assertEquals(
                1
                , count($queues)
        );
        $this->assertEquals(
                'Queue_Name_1'
                , $queues[0]
        );
    }

    public function test_deleteQueue()
    {
        foreach ($this->_queuesList as $queueName) {
            $this->object->createQueue($queueName);
        }
        foreach ($this->_messageList as $message) {
            $this->object->addMessage('nextQueue21', $message);
        }
        $queues = $this->object->listQueues();
        $this->assertEquals(
                4
                , count($queues)
        );
        $this->object->deleteQueue('nextQueue21');
        $this->object->deleteQueue('nextQueue22');
        $queues = $this->object->listQueues();
        $this->assertEquals(
                2
                , count($queues)
        );
        $number = count($this->object->getMessagesDataStore()->query(new query()));
        $this->assertEquals(
                0
                , $number
        );
    }

    public function test_listQueues()
    {
        foreach ($this->_queuesList as $queueName) {
            $this->object->createQueue($queueName);
        }
        $queues = $this->object->listQueues();
        $this->assertEquals(
                $this->_queuesList
                , $queues
        );

        $queues = $this->object->listQueues('Next');
        $this->assertEquals(
                2
                , count($queues)
        );
    }

    public function test_purgeQueuePriority()
    {
        $this->object->createQueue('nextQueue21');
        $this->object->addMessage('nextQueue21', $this->_messageList[0], 'HIGH');
        $this->object->addMessage('nextQueue21', $this->_messageList[1], 'HIGH');
        $this->object->addMessage('nextQueue21', $this->_messageList[2], 'LOW');
        $this->object->addMessage('nextQueue21', $this->_messageList[3], 'MID');
        $this->object->addMessage('nextQueue21', $this->_messageList[4], 'MID');

        $this->object->purgeQueue('nextQueue21', 'HIGH');
        $number = $this->object->getNumberMessages('nextQueue21');
        $this->assertEquals(
                3
                , $number
        );
        $this->object->purgeQueue('nextQueue21', 'MID');
        $number = $this->object->getNumberMessages('nextQueue21');
        $this->assertEquals(
                1
                , $number
        );
    }

    public function test_purgeQueue()
    {
        $this->object->createQueue('nextQueue21');
        $this->object->addMessage('nextQueue21', $this->_messageList[0], 'HIGH');
        $this->object->addMessage('nextQueue21', $this->_messageList[1], 'HIGH');
        $this->object->addMessage('nextQueue21', $this->_messageList[2], 'LOW');
        $this->object->addMessage('nextQueue21', $this->_messageList[3], 'MID');
        $this->object->addMessage('nextQueue21', $this->_messageList[4]);

        $this->object->purgeQueue('nextQueue21');
        $number = $this->object->getNumberMessages('nextQueue21');
        $this->assertEquals(
                0
                , $number
        );
    }

    public function test_addMessage()
    {
        foreach ($this->_queuesList as $queueName) {
            $this->object->createQueue($queueName);
        }
        foreach ($this->_messageList as $message) {
            $this->object->addMessage('nextQueue21', $message);
        }
        //$m = $this->object->getMessagesDataStore()->query(new query);
        //var_dump($m);
        $number = $this->object->getNumberMessages('nextQueue21');
        $this->assertEquals(
                5
                , $number
        );
    }

    public function test_isEmpty()
    {
        $this->object->createQueue('nextQueue21');
        $this->object->addMessage('nextQueue21', $this->_messageList[0], 'HIGH');
        $this->object->addMessage('nextQueue21', $this->_messageList[4]);

        $this->object->createQueue('nextQueue22');

        $this->assertFalse($this->object->isEmpty('nextQueue21'));
        $this->assertTrue($this->object->isEmpty('nextQueue22'));
    }

    public function test_deleteMessage()
    {
        $this->object->createQueue('nextQueue22');
        $this->object->addMessage('nextQueue22', $this->_messageList[0], 'HIGH');
        $this->object->addMessage('nextQueue22', $this->_messageList[4]);
        $m1 = $this->object->getMessages('nextQueue22', 1)[0];

        $this->assertFalse($this->object->isEmpty('nextQueue22'));
        $m2 = $this->object->getMessages('nextQueue22', 1)[0];
        $this->object->deleteMessage('nextQueue22', $m1);
        $this->object->deleteMessage('nextQueue22', $m2);
        $this->assertTrue($this->object->isEmpty('nextQueue22'));
    }

    public function test_getMessagesPriority()
    {
        $this->object->createQueue('nextQueue21');
        $this->object->addMessage('nextQueue21', $this->_messageList[0], 'HIGH');
        $this->object->addMessage('nextQueue21', $this->_messageList[1], 'HIGH');
        $this->object->addMessage('nextQueue21', $this->_messageList[2], 'LOW');
        $this->object->addMessage('nextQueue21', $this->_messageList[3], 'MID');
        $this->object->addMessage('nextQueue21', $this->_messageList[4], 'MID');

        $m1 = $this->object->getMessages('nextQueue21', 1, 'LOW');
        $this->assertEquals(
                1
                , count($m1)
        );
        $m2 = $this->object->getMessages('nextQueue21', 10, 'HIGH');
        $this->assertEquals(
                2
                , count($m2)
        );
        $m3 = $this->object->getMessages('nextQueue21', 3, 'HIGH');
        $this->assertEquals(
                0
                , count($m3)
        );
        $number = $this->object->getNumberMessages('nextQueue21');
        $this->assertEquals(
                2
                , $number
        );
    }

    public function test_getMessagesSort()
    {
        $this->object->createQueue('nextQueue21');
        $this->object->addMessage('nextQueue21', $this->_messageList[0], 'LOW');
        $this->object->addMessage('nextQueue21', $this->_messageList[1], 'HIGH');
        $this->object->addMessage('nextQueue21', $this->_messageList[2], 'LOW');
        $this->object->addMessage('nextQueue21', $this->_messageList[3], 'LOW');
        $this->object->addMessage('nextQueue21', $this->_messageList[4], 'MID');

        $m1 = $this->object->getMessages('nextQueue21', 3);
        $this->assertEquals(
                'HIGH'
                , $m1[0][DataStores::PRIORITY]
        );
        $this->assertEquals(
                'MID'
                , $m1[1][DataStores::PRIORITY]
        );
        $this->assertEquals(
                'LOW'
                , $m1[2][DataStores::PRIORITY]
        );
    }

    public function test_getMessagesDataType()
    {
        $this->object->createQueue('nextQueue21');
        foreach ($this->_messageList as $value) {
            $this->object->addMessage('nextQueue21', $value);
            $result = $this->object->getMessages('nextQueue21', 1);
            $this->assertEquals(
                    $value
                    , $result[0][DataStores::MESSAGE_BODY]
            );
        }
    }

    public function test_returnToQueueAfterTime()
    {
        $this->object->setMaxTimeInFlight(5);
        $this->object->createQueue('nextQueue21');
        foreach ($this->_messageList as $value) {
            $this->object->addMessage('nextQueue21', $value);
        }
        $messages = $this->object->getMessages('nextQueue21', 3);
        $number2 = $this->object->getNumberMessages('nextQueue21');
        $this->assertEquals(
                2
                , $number2
        );
        $this->object->deleteMessage('nextQueue21', $messages[0]);
        var_dump($this->object->getMessagesDataStore()->query(new query));
        sleep(6);
        $number4 = $this->object->getNumberMessages('nextQueue21');
        $this->assertEquals(
                4
                , $number4
        );
        $this->object->setMaxTimeInFlight();
    }

    /*
      public function test_getMessagesSort()
      {
      $this->object->createQueue('nextQueue21');
      $this->object->addMessage('nextQueue21', $this->_messageList[0], 'HIGH');
      $this->object->addMessage('nextQueue21', $this->_messageList[1], 'HIGH');
      $this->object->addMessage('nextQueue21', $this->_messageList[2], 'LOW');
      $this->object->addMessage('nextQueue21', $this->_messageList[3], 'HIGH');
      $this->object->addMessage('nextQueue21', $this->_messageList[4], 'HIGH');
      $this->object->addMessage('nextQueue21', $this->_messageList[0], 'HIGH');
      $this->object->addMessage('nextQueue21', $this->_messageList[1], 'HIGH');
      $this->object->addMessage('nextQueue21', $this->_messageList[2], 'HIGH');
      $this->object->addMessage('nextQueue21', $this->_messageList[3], 'HIGH');
      $this->object->addMessage('nextQueue21', $this->_messageList[4], 'MID');
      $m1 = $this->object->getMessages('nextQueue21', 3);
      $this->assertEquals(
      'HIGH'
      , $m1[0][DataStores::PRIORITY]
      );
      $this->assertEquals(
      'MID'
      , $m1[1][DataStores::PRIORITY]
      );
      $this->assertEquals(
      'LOW'
      , $m1[2][DataStores::PRIORITY]
      );
      }
     */
}
