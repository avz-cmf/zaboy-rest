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

class DataStores implements AdapterInterface
{

    const DEFAULT_MAX_TIME_IN_FLIGHT = 30; //http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/AboutVT.html
    const PRIORITY_SEPARATOR = '_';
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

    /** @var PriorityHandlerInterface $priorityHandler */
    private $priorityHandler;

    /** @var DataStoresInterface $queuesDataStore */
    private $queuesDataStore;

    /** @var DataStoresInterface $messagesDataStore */
    private $messagesDataStore;

    /** @var int $messagesDataStore */
    private $maxTimeInFlight;

    /**
     *
     * @param DataStoresInterface $queuesDataStore
     * @param DataStoresInterface $messagesDataStore
     * @throws RestException
     */
    public function __construct(DataStoresInterface $queuesDataStore, DataStoresInterface $messagesDataStore)
    {
        if (is_null($queuesDataStore) || is_null($messagesDataStore)) {
            throw new RestException('Argument not defined.');
        }

        $this->queuesDataStore = $queuesDataStore;
        $this->messagesDataStore = $messagesDataStore;
        $this->priorityHandler = new PriorityHandler();
        $this->maxTimeInFlight = self::DEFAULT_MAX_TIME_IN_FLIGHT;
    }

    /**
     * @inheritdoc
     */
    public function addMessage($queueName, $message, $priority = null)
    {
        if (empty($queueName)) {
            throw new RestException('Parameter queueName empty or not defined.');
        }
        if (empty($message)) {
            throw new RestException('Parameter message empty or not defined.');
        }
        $identifier = $this->messagesDataStore->getIdentifier();
        $id = uniqid($queueName
                . self::PRIORITY_SEPARATOR
                . $priority
                . self::PRIORITY_SEPARATOR
                , true
        );
        $priorityIndex = $this->getPriorityIndex($priority);
        $new_message = [
            $identifier => $id,
            self::QUEUE_NAME => $queueName,
            self::MESSAGE_BODY => serialize($message),
            self::PRIORITY => $priorityIndex,
            self::TIME_IN_FLIGHT => 0,
        ];
        $this->messagesDataStore->create($new_message);
        return $this;
    }

    /**
     * @param string $queueName
     * @param array  $messages
     * @param string $priority
     *
     * @return QueueClientInterface
     */
    public function addMessages($queueName, $messages, $priority = null)
    {
        foreach ($messages as $message) {
            $this->addMessage($queueName, $message, $priority);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMessages($queueName, $numberMsg = 1, $priority = null)
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
        $limitNode = new Node\LimitNode($numberMsg, 0);
        $query->setLimit($limitNode);
        $sortNode = new Node\SortNode([self::PRIORITY => Node\SortNode::SORT_ASC]);
        $query->setSort($sortNode);
        $messages = $this->messagesDataStore->query($query);
        $identifier = $this->messagesDataStore->getIdentifier();
        foreach ($messages as $key => $message) {
            $updateMassage[$identifier] = $message[$identifier];
            $updateMassage[self::TIME_IN_FLIGHT] = time();
            $this->messagesDataStore->update($updateMassage);

            $message[self::MESSAGE_BODY] = unserialize($message[self::MESSAGE_BODY]);
            $priorityIndex = $message[self::PRIORITY];
            $message[self::PRIORITY] = $this->getPriority($priorityIndex);
            $messages[$key] = $message;
        }
        return $messages;
    }

    /**
     * @inheritdoc
     */
    public function deleteMessage($queueName, $message)
    {
        if (empty($queueName)) {
            throw new RestException('Parameter queueName empty or not defined.');
        }
        if (empty($message)) {
            throw new RestException('Parameter message empty or not defined.');
        }
        if (!is_array($message)) {
            throw new RestException('message must be an array.');
        }
        $identifier = $this->messagesDataStore->getIdentifier();
        if (!isset($message[$identifier])) {
            throw new RestException('Message identifier not found in message.');
        }
        $this->messagesDataStore->delete($message[$identifier]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isEmpty($queueName, $priority = null)
    {
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
        $selectNode = new Node\SelectNode([self::PRIORITY]);
        $query->setSelect($selectNode);
        $messages = $this->messagesDataStore->query($query);
        return count($messages) > 0 ? false : true;
    }

    /**
     * @inheritdoc
     */
    public function getNumberMessages($queueName, $priority = null)
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
        $selectNode = new Node\SelectNode([self::PRIORITY]);
        $query->setSelect($selectNode);
        $messages = $this->messagesDataStore->query($query);
        return count($messages);
    }

    /**
     * @inheritdoc
     */
    public function deleteQueue($queueName)
    {
        $this->purgeQueue($queueName);
        $this->queuesDataStore->delete($queueName);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function createQueue($queueName)
    {
        $identifier = $this->queuesDataStore->getIdentifier();
        $itemData = [$identifier => $queueName];
        $this->queuesDataStore->create($itemData, true);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function renameQueue($sourceQueueName, $targetQueueName)
    {
        $identifier = $this->messagesDataStore->getIdentifier();
        $query = new Query();
        $scalarNodeQueue = new ScalarOperator\EqNode(
                self::QUEUE_NAME, $sourceQueueName
        );
        $query->setQuery($scalarNodeQueue);
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $messages = $this->messagesDataStore->query($query);
        $this->createQueue($targetQueueName);
        foreach ($messages as $message) {
            $message[self::QUEUE_NAME] = $targetQueueName;
            $this->messagesDataStore->update($message);
        }
        $this->deleteQueue($sourceQueueName);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function purgeQueue($queueName, $priority = null)
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
        $messages = $this->messagesDataStore->query($query);
        foreach ($messages as $message) {
            $id = $message[$identifier];
            $this->messagesDataStore->delete($id);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function listQueues($prefix = '')
    {
        $identifier = $this->messagesDataStore->getIdentifier();
        $query = new Query();
        if ($prefix !== '') {
            $likeNode = new ScalarOperator\LikeNode(
                    $identifier, new Glob($prefix . '*')
            );
            $query->setQuery($likeNode);
        }
        $queues = $this->queuesDataStore->query($query);
        $result = [];
        foreach ($queues as $queue) {
            $id = $queue[$identifier];
            $result[] = $id;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getPriorityHandler()
    {
        return $this->priorityHandler;
    }

    /**
     * @inheritdoc
     */
    public function getQueuesDataStore()
    {
        return $this->queuesDataStore;
    }

    /**
     * @inheritdoc
     */
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

    /**
     *
     * @param int $time
     */
    public function setMaxTimeInFlight($time = null)
    {
        $this->maxTimeInFlight = !$time ? self::DEFAULT_MAX_TIME_IN_FLIGHT : $time;
    }

}
