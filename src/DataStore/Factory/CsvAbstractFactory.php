<?php

namespace zaboy\rest\DataStore\Factory;

use Interop\Container\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use zaboy\rest\DataStore\DataStoreException;

class CsvAbstractFactory extends AbstractDataStoreFactory
{

    const KEY_FILENAME = 'filename';
    const KEY_DELIMITER = 'delimiter';
    static $KEY_DATASTORE_CLASS = 'zaboy\rest\DataStore\CsvBase';
    protected static $KEY_IN_CREATE = 0;

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($this::$KEY_IN_CREATE) {
            throw new DataStoreException("Create will be called without pre call canCreate method");
        }
        $this::$KEY_IN_CREATE = 1;

        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];
        if (!isset($serviceConfig[self::KEY_FILENAME])) {
            $this::$KEY_IN_CREATE = 0;
            throw new DataStoreException(sprintf('The file name for "%s" is not specified in the config \'dataStore\'', $requestedName));
        }
        $filename = $serviceConfig[self::KEY_FILENAME];
        $delimiter = (isset($serviceConfig[self::KEY_DELIMITER]) ? $serviceConfig[self::KEY_DELIMITER] : null);
        $lockHandler = new LockHandler($filename);

        $this::$KEY_IN_CREATE = 0;

        return new $requestedClassName($filename, $delimiter, $lockHandler);
    }


}