<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Queue\Broker;

use ReputationVIP\QueueClient\QueueClientInterface;
use ReputationVIP\QueueClient\QueueClient;

/**
 * QueueBrokerInterhace Interface for Queue Client
 *
 * @category   rest
 * @package    zaboy
 */
class QueueBroker
{

    /**
     * <code>
     * [
     *      QueueClient => [
     *          Queue1 => [
     *              'workerName' => 'callbackService Name1'
     *          ]
     *          Queue2 => [
     *              'workerName' => 'callbackService Name1'
     *          ]
     *      ],
     *      nextQueueClient => [
     *          nextQueue1 => [...
     * ]
     * </code>
     *
     * @var array
     */
    var $queuesParams;

    /**
     * <code>
     * [
     *      QueueClient => object,
     *      nextQueueClient => object,
     * ]
     * </code>
     *
     * @var array
     */
    var $queuesClientsInstanses;
    var $callbackManager;

    public function __construct($callbackManager, $queuesParams, $queuesClientsInstanses)
    {
        $this->callbackManager = $callbackManager;

        $this->queuesParams = $queuesParams;
        $this->queuesClientsInstanses = $queuesClientsInstanses;
    }

    public function runAllWorkers()
    {
        $this->runWorkers();
    }

    public function runHighPriorityWorkers()
    {
        $this->runWorkers('HIGH');
    }

    protected function runWorkers($priority = null)
    {
        $queueClientsNames = $this->getQueueClientsNames();
        foreach ($queueClientsNames as $queueClientName) {
            $queuesNames = $this->getQueuesByClient($queueClientName);
            foreach ($queuesNames as $queueName) {
                $worker = $this->getQueueWorkerInstanse($queueClientName, $queueName);
                $queueClient = $this->getQueuesCleentsInstanse($queueClientName);
                $numberOfMessages = $queueClient->getNumberMessages($queueName);
                $messages = $queueClient->getMessages($queueName, $numberOfMessages, $priority);
                foreach ($messages as $message) {
                    $worker->call($message); //use $message['Body']
                }
            }
        }
    }

    /**
     *
     * @return QueueClient
     */
    protected function getQueueClientsNames()
    {
        return array_keys($this->queuesParams);
    }

    /**
     *
     * @param string $queueClientName
     * @return QueueClientInterface
     */
    protected function getQueuesCleentsInstanse($queueClientName)
    {
        return $this->queuesClientsInstanses[$queueClientName];
    }

    protected function getQueuesByClient($queueClientName)
    {
        return array_keys($this->queuesParams[$queueClientName]);
    }

    protected function getQueueWorkerInstanse($queueClientName, $queueName)
    {
        $queueClientParams = $this->queuesParams[$queueClientName];
        $workerName = $queueClientParams[$queueName]['workerName'];
        $worker = $this->callbackManager->get($workerName);
        return $worker;
    }

}
