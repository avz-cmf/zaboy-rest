<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 17.12.16
 * Time: 10:23 AM
 */

namespace zaboy\rest\Logger;

use Psr\Log\AbstractLogger;
use zaboy\rest\DataStore\Interfaces\DataStoresInterface;

class LoggerDS extends AbstractLogger
{

    /** @var  DataStoresInterface */
    protected $logDS;

    public function __construct(DataStoresInterface $dataStore)
    {
        $this->logDS = $dataStore;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {

        $replace = [];
        foreach ($context as $key => $value) {
            if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        $this->logDS->create([
            'level' => $level,
            'message' => strtr($message, $replace)
        ]);
    }
}