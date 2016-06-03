<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Pipe;

use Zend\Stratigility\MiddlewarePipe;

/**
 * Pipe for execute REST calls
 *
 * @category   rest
 * @package    zaboy
 *
 */
class RestRql extends MiddlewarePipe
{

    /**
     *
     * @param array $middlewares
     */
    public function __construct($middlewares)
    {
        parent::__construct();
        foreach ($middlewares as $middleware) {
            $this->pipe($middleware);
        }
    }

}
