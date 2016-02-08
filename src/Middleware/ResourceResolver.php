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
 * Extracts resource name and row id from URL
 * 
 * If URL is 'site,com/api/rest/RESOURCE-NAME/ROWS-ID'
 * request->getAttribute('Resource-Name') returns 'RESOURCE-NAME'
 * request->getAttribute('id') returns 'ROWS-ID'
 * 
 * If URL is 'site,com/restapi/RESOURCE-NAME?a=1&limit(2,5)'
 * request->getAttribute('Resource-Name') returns 'RESOURCE-NAME'
 * request->getAttribute('id') returns null
 * 
 * @category   Rest
 * @package    Rest
 */
class ResourceResolver implements MiddlewareInterface
{
    
    /**
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $path = $request->getUri()->getPath();        
        preg_match("/^[\/]?([-_A-Za-z0-9]+)/",$path,$matches);
        $resourceName = isset($matches[1])?$matches[1]:null;
        preg_match("/^[\/]?([-_A-Za-z0-9]+)[\/]([-_A-Za-z0-9]+)/",$path,$matches);
        $id = isset($matches[2])?$matches[2]:null;

        $request = $request->withAttribute('Resource-Name', $resourceName);
        $request = $request->withAttribute('id', $id);

        if ($next) {
            return $next($request, $response);
        }
        return $response;
    }
}