<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Middleware;

use zaboy\rest\DataStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use zaboy\rest\DataStore\Interfaces\DataStoresInterface;
use Zend\Stratigility\MiddlewareInterface;

/**
 * Middleware which contane DataStore
 *
 * @category   rest
 * @package    zaboy
 */
abstract class DataStoreAbstract implements MiddlewareInterface
{

    /**
     *
     * @var DataStoresInterface
     */
    protected $dataStore;

    /**
     *
     * @param DataStore\DataStoreAbstract $dataStore
     */
    public function __construct(DataStore\DataStoreAbstract $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    abstract public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null);
}
