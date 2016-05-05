<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Queue\DataStore;

use zaboy\rest\DataStore\DataStoreAbstract;
use zaboy\rest\DataStore\DataStoreException;
use ReputationVIP\QueueClient\QueueClient;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use ReputationVIP\QueueClient\Adapter\FileAdapter;
use zaboy\rest\DataStore\ConditionBuilder\PhpConditionBuilder;

/**
 * DataStores as Queues
 *
 * @see http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 * @category   rest
 * @package    zaboy
 */
class Queues extends DataStoreAbstract
{

    /**
     * @var QueueClient
     */
    protected $queueClient;

    public function __construct(AdapterInterface $adapter = null)
    {
        $this->conditionBuilder = new PhpConditionBuilder;
        if (is_null($adapter)) {
            $adapter = new FileAdapter('/tmp');
        }
        $this->queueClient = new QueueClient($adapter);
    }

//** Interface "zaboy\rest\DataStore\Interfaces\ReadInterface" **/
    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->checkIdentifierType($id);
        $queuesArray = $this->queueClient->listQueues();
        if (in_array($id, $queuesArray)) {
            $identifier = $this->getIdentifier();
            $queue = [$identifier => $id];
            return $queue;
        } else {
            return null;
        }
    }

// ** Interface "zaboy\rest\DataStore\Interfaces\DataStoresInterface"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        $identifier = $this->getIdentifier();
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Queue name must be set as "id" = "QueuName" ');
        }
        $id = $itemData[$identifier]; //queueName
        if (!($this->has($id))) {
            $this->queueClient->createQueue($id);
        } elseif (!$rewriteIfExist) {
            throw new DataStoreException('Queue is already existed with name =  ' . $itemData[$identifier]);
        } elseif (!$rewriteIfExist) {
            $this->queueClient->deleteQueue($id);
            $this->queueClient->createQueue($id);
        }
        $this->items[$id] = array_merge(array($identifier => $id), $itemData);
        return $this->items[$id];
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        throw new DataStoreException('Update is not supported for Queues Data Store');
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->checkIdentifierType($id);
        if ($this->has($id)) {
            $this->queueClient->deleteQueue($id);
            $deletedItemsCount = 1;
        } else {
            $deletedItemsCount = 0;
        }
        return $deletedItemsCount;
    }

// ** Interface "/Coutable"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function count()
    {
        $queuesArray = $this->queueClient->listQueues();
        return count($queuesArray);
    }

// ** Interface "/IteratorAggregate"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getQueueClient()
    {
        return $this->queueClient;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $identifier = $this->getIdentifier();
        $queuesArray = $this->queueClient->listQueues();
        $itemsArray = [];
        foreach ($queuesArray as $queueName) {
            $itemsArray[$queueName] = [$identifier => $queueName];
        }
        return new \ArrayIterator($itemsArray);
    }

// ** protected  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    protected function getKeys()
    {
        $queuesArray = $this->queueClient->listQueues();
        return $queuesArray;
    }

}
