<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Queue\Broker;

use ReputationVIP\QueueClient\QueueClientInterface;

/**
 * QueueBrokerInterhace Interface for Queue Client
 *
 * @category   rest
 * @package    zaboy
 */
interface QueueBrokerInterhace
{

    /**
     * Run workers for all queues which are maneged by this broker
     */
    public function runAllWorkers();

    /**
     * Run workers for highpriority queues only  which are maneged by this broker
     */
    public function runHighPriorityWorkers();
}
