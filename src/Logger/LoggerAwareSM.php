<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 17.12.16
 * Time: 11:41 AM
 */

namespace zaboy\rest\Logger;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Zend\ServiceManager\ServiceManager;

class LoggerAwareSM implements LoggerAwareInterface
{
    /** @var  ServiceManager */
    static protected $serviceManager;

    public function __construct(ServiceManager $sm)
    {
        if(!isset($this::$serviceManager)) {
            $this::$serviceManager = $sm;
        }
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this::$serviceManager->setService('logger', $logger);
    }
}