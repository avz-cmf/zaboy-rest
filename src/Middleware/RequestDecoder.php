<?php
/**
 * Zaboy lib (http://zaboy.org/lib/)
 * 
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\MiddlewareInterface;

/**
 * Parse body fron JSON and add result array to $request->withParsedBody()
 * 
 * @category   Rest
 * @package    Rest
 */
class RequestDecoder implements MiddlewareInterface
{
    
    /**
     * 
     * @todo Add develope mode for debug with HTML POST and GET
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $contenttype = $request->getHeader('Content-Type');
        if (false !== strpos($contenttype[0], 'json')) {
            $body = json_decode($request->getBody(), true);
            $request = $request->withParsedBody($body);            
        } else {
            //todo XML?
        }
        if ($next) {
            return $next($request, $response);
        }
        return $response;      
    }
}