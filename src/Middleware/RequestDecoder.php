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
     * @todo positionHeaders = 'beforeId'  'Put-Default-Position'  'Put-Default-Position'
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        // @see https://github.com/SitePen/dstore/blob/21129125823a29c6c18533e7b5a31432cf6e5c56/src/Rest.js
        $overwriteModeHeader = $request->getHeader('If-Match');
        $overwriteMode = $overwriteModeHeader === '*' ? true : false;
        $request = $request->withAttribute('Overwrite-Mode', $overwriteMode);
        
        $putDefaultPosition = $request->getHeader('Put-Default-Position'); //'start' : 'end'
        if (isset($putDefaultPosition)) {
            $request = $request->withAttribute('Put-Default-Position', $putDefaultPosition); 
        }
        // @see https://github.com/SitePen/dstore/issues/42   
        $putBeforeHeader = $request->getHeader('Put-Before');           
        $putBefore = !empty($putBeforeHeader);
        $request = $request->withAttribute('Put-Before', $putBefore);
     
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