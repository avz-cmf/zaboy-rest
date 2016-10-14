<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\DataStore\DataStoreException;

/**
 * Create and return an instance of the DataStore which based on Http Client
 *
 * The configuration can contain:
 * <code>
 * 'DataStore' => [
 *
 *     'HttpClient' => [
 *         'class' => 'zaboy\rest\DataStore\HttpDatastoreClassname',
 *          'url' => 'http://site.com/api/resource-name',
 *          'options' => ['timeout' => 30]
 *     ]
 * ]
 * </code>
 *
 * @category   rest
 * @package    zaboy
 */
class HttpClientAbstractFactory extends AbstractDataStoreFactory
{
    const KEY_URL = 'url';
    const KEY_OPTIONS = 'options';
    static $KEY_DATASTORE_CLASS = 'zaboy\rest\DataStore\HttpClient';
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
        if (isset($serviceConfig[self::KEY_URL])) {
            $url = $serviceConfig[self::KEY_URL];
        } else {
            $this::$KEY_IN_CREATE = 0;
            throw new DataStoreException(
                'There is not url for ' . $requestedName . 'in config \'dataStore\''
            );
        }
        if (isset($serviceConfig[self::KEY_OPTIONS])) {
            $options = $serviceConfig[self::KEY_OPTIONS];
            $result = new $requestedClassName($url, $options);
        } else {
            $result = new $requestedClassName($url);
        }
        $this::$KEY_IN_CREATE = 0;
        return $result;
    }



}
