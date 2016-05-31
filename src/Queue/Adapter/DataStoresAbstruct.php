<?php

namespace zaboy\rest\Queue\Adapter;

use zaboy\rest\DataStore\Interfaces\DataStoresInterface;
use zaboy\rest\Queue\PriorityHandler\PriorityHandler;
use zaboy\rest\RestException;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use Xiag\Rql\Parser\Node\Query\ScalarOperator;
use Xiag\Rql\Parser\Node\Query\LogicOperator;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\Node;
use Xiag\Rql\Parser\DataType\Glob;
use zaboy\rest\DataStore\DataStoreException;

abstract class DataStoresAbstruct
{

    /**
     *
     * @see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/AboutVT.html
     */
    const DEFAULT_MAX_TIME_IN_FLIGHT = 30;
    const ID_SEPARATOR = '_';
    const IN_FLY = 'IN_FLY_';
    //
    //QUEUES_DATA_STORE
    //'id' - queue name
    const TIME_IN_FLIGHT = 'time_in_flight';
    //
    //MESSAGES_DATA_STORE
    //'id' - unic id of message like: QueueName_LOW_jkkljnk;jn5kjh.95kj5ntk4
    const QUEUE_NAME = 'queue_name';
    const MESSAGE_BODY = 'message_body';
    const PRIORITY = 'priority';
    const CREATED_ON = 'created_on';

    /** @var int $messagesDataStore */
    protected $maxTimeInFlight;

    /** @var DataStoresInterface $queuesDataStore */
    protected $queuesDataStore;

    /** @var DataStoresInterface $messagesDataStore */
    protected $messagesDataStore;

    /** @var PriorityHandlerInterface $priorityHandler */
    protected $priorityHandler;

    /**
     *
     * @param string $queueName
     * @param string $priority
     * @return array
     */
    protected function getMessage($queueName, $priority = null)
    {
        $identifier = $this->messagesDataStore->getIdentifier();
        for ($attemptNumber = 1; $attemptNumber < 10; $attemptNumber++) {
            var_dump('$attemptNumber');
            var_dump($attemptNumber);
            $messages = $this->readMessageFifoButNewerFlightInFirst($queueName, $priority, $attemptNumber);
            if (empty($messages)) {
                return null;
            }
            foreach ($messages as $message) {
                $id = $message[$identifier];
                $idInFly = $this->increaseInFlightNumberInId($id);
                //"0_nextQueue21_HIGH_572ca3202b3bf1.21681861" --> "1_nextQueue21_HIGH_572ca3202b3bf1.21681861"
                $flyIdMessage = array_merge($message, array($identifier => $idInFly, self::TIME_IN_FLIGHT => time()));
                try {
                    $this->messagesDataStore->create($flyIdMessage);
                    var_dump('try');
                    var_dump($flyIdMessage['id']);
                } catch (DataStoreException $exc) {
                    var_dump('catch');
                    var_dump($flyIdMessage['id']);
                    continue;
                }
                $this->messagesDataStore->delete($id); /*
                  $message = array_merge($flyIdMessage, array($identifier => $id, self::TIME_IN_FLIGHT => time()));
                  $this->messagesDataStore->create($message, true);
                  $this->messagesDataStore->delete($idInFly);

                  $message[self::MESSAGE_BODY] = unserialize($message[self::MESSAGE_BODY]);
                  $priorityIndex = $message[self::PRIORITY];
                  $message[self::PRIORITY] = $this->getPriority($priorityIndex);
                  return $message; */
                $flyIdMessage[self::MESSAGE_BODY] = unserialize($message[self::MESSAGE_BODY]);
                $priorityIndex = $message[self::PRIORITY];
                $flyIdMessage[self::PRIORITY] = $this->getPriority($priorityIndex);
                return $flyIdMessage;
            }
        }
        return null;
    }

    /**
     *
     * @param string $queueName
     * @param string $priority
     * @param int $attemptNumber Adjustable parametr for search
     * @return array
     */
    protected function readMessageFifoButNewerFlightInFirst($queueName, $priority = null, $attemptNumber = 1)
    {
        $query = new Query();
        $scalarNodeQueue = new ScalarOperator\EqNode(
                self::QUEUE_NAME, $queueName
        );
        $scalarNodeNotInFlihgt = new ScalarOperator\EqNode(
                self::TIME_IN_FLIGHT, 0
        );
        $scalarNodeLongnInFlihgt = new ScalarOperator\LtNode(
                self::TIME_IN_FLIGHT, time() - $this->getMaxTimeInFlight()
        );
        $orNodeInFlihgt = new LogicOperator\OrNode([
            $scalarNodeNotInFlihgt,
            $scalarNodeLongnInFlihgt
        ]);
        if (isset($priority)) {
            $scalarNodePriority = new ScalarOperator\EqNode(
                    self::PRIORITY, $this->getPriorityIndex($priority)
            );
            $andNode = new LogicOperator\AndNode([
                $orNodeInFlihgt,
                $scalarNodePriority,
                $scalarNodeQueue
            ]);
        } else {
            $andNode = new LogicOperator\AndNode([
                $orNodeInFlihgt,
                $scalarNodeQueue
            ]);
        }
        $query->setQuery($andNode);

        $limit = $attemptNumber * 2;
        $offset = $attemptNumber < 5 ? 0 : ($attemptNumber - 5) * 5;
        $limitNode = new Node\LimitNode($limit, $offset);
        $query->setLimit($limitNode);

        $sortNode = new Node\SortNode([
            self::TIME_IN_FLIGHT => Node\SortNode::SORT_ASC
            , self::PRIORITY => Node\SortNode::SORT_ASC
            , self::CREATED_ON => Node\SortNode::SORT_ASC
        ]);
        $query->setSort($sortNode);
        $messages = $this->messagesDataStore->query($query);

        return $messages;
    }

    /**
     *
     * @param string $queueName
     * @param string $priority
     * @return array
     */
    protected function readAllMessagesIdFromQueue($queueName, $priority)
    {
        $identifier = $this->messagesDataStore->getIdentifier();
        $query = new Query();
        $scalarNodeQueue = new ScalarOperator\EqNode(
                self::QUEUE_NAME, $queueName
        );
        if (isset($priority)) {
            $scalarNodePriority = new ScalarOperator\EqNode(
                    self::PRIORITY, $this->getPriorityIndex($priority)
            );
            $node = new LogicOperator\AndNode([
                $scalarNodePriority,
                $scalarNodeQueue
            ]);
        } else {
            $node = $scalarNodeQueue;
        }
        $query->setQuery($node);
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $messagesWithIdOnly = $this->messagesDataStore->query($query);

        return $messagesWithIdOnly;
    }

    public function getQueuesDataStore()
    {
        return $this->queuesDataStore;
    }

    public function getMessagesDataStore()
    {
        return $this->messagesDataStore;
    }

    /**
     *
     * @param string $priority
     * @return int
     */
    protected function getPriorityIndex($priority)
    {
        if (is_null($priority)) {
            $priorityIndex = $this->getPriorityHandler()->getDefault();
        }
        $priorityArray = $this->getPriorityHandler()->getAll();
        $priorityIndex = array_search($priority, $priorityArray);

        return $priorityIndex;
    }

    /**
     *
     * @param int $priorityIndex
     * @return string
     */
    protected function getPriority($priorityIndex)
    {
        $priority = $this->getPriorityHandler()->getName($priorityIndex);
        return $priority;
    }

    /**
     *
     * @return int
     */
    public function getMaxTimeInFlight()
    {
        return $this->maxTimeInFlight;
    }

    public function setMaxTimeInFlight($time = null)
    {
        $this->maxTimeInFlight = !$time ? self::DEFAULT_MAX_TIME_IN_FLIGHT : $time;
    }

    /**
     * "0_nextQueue21_HIGH_572ca3202b3bf1.21681861" --> "1_nextQueue21_HIGH_572ca3202b3bf1.21681861"
     *
     * @todo callback for "message with id - $id, can not be resolved"?
     * @param int string
     */
    public function increaseInFlightNumberInId($id)
    {
        $numberInFly = (int) substr($id, 0, 1) + 1;
        $inFlyId = $numberInFly . substr($id, 1, strlen($id) - 1);
        if ($inFlyId === 9) {
            throw new RestException("message with id - $id, can not be resolved");
        }
        return $inFlyId;
    }

}
