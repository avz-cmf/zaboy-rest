<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Queue\Broker\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\FactoryAbstract;
use zaboy\rest\RestException;
use zaboy\rest\Queue\Broker;
use \zaboy\scheduler\Callback\CallbackManager;

/**
 * Create and return an instance of the array in Memory
 *
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 * 'queueBroker' =>[
 *      'ManagedQueueClient' => [
 *          'ManagedQueue1' => [
 *              'workerName' => 'someCallBack'
 *          ]
 *          'ManagedQueue2' => [
 *              'workerName' => 'anotherCallBack'
 *          ]
 *      ],
 *      'nextManagedQueueClient' => [
 *          'nextManagedQueue1' => [...
 * ]
 * </code>
 *
 * @category   rest
 * @package    zaboy
 */
class QueueBrokerFactory extends FactoryAbstract
{

    /**
     * Create and return an instance of the QueueBroker.
     *
     * @param  Interop\Container\ContainerInterface $container
     * @return zaboy\rest\Queue\Broker
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $serviceConfig = $config['queues']['queueBroker'];
        $queueClientsNames = array_keys($serviceConfig);
        $queuesClientsInstanses = [];
        foreach ($queueClientsNames as $queueClientName) {
            $queuesClientsInstanses[] = $container->get($queueClientName);
        }
        if ($container->has(CallbackManager::SERVICE_NAME)) {
            /* @var \zaboy\scheduler\Callback\CallbackManager $callbackManager */
            $callbackManager = $container->get(CallbackManager::SERVICE_NAME);
        } else {
            $callbackManager = new CallbackManager($container);
        }
        return new Broker($callbackManager, $serviceConfig, $queuesClientsInstanses);
    }

}
