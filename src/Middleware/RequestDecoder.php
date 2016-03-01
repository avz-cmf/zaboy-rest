<?php
/**
 * Zaboy lib (http://zaboy.org/lib/)
 * 
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Middleware;

use Psr\Http\Message\ResponseInterface;
use zaboy\rest\RestException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\MiddlewareInterface;
use Zend\Json\Json;

/**
 * Parse body fron JSON and add result array to $request->withParsedBody()
 * 
 * @category   Rest
 * @package    Rest
 */
class RequestDecoder implements MiddlewareInterface
{
    
    /**                         Location: http://www.example.com/users/4/
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
            $body = $this->jsonDecode($request->getBody());
            $request = $request->withParsedBody($body);            
        } else {
            //todo XML?
        }
        if ($next) {
            return $next($request, $response);
        }
        return $response;      
    }
    
    protected function jsonDecode($data)
    {
        // Clear json_last_error()
        json_encode(null);

        $result = Json::decode($data, Json::TYPE_ARRAY);//json_decode($data);
        json_encode(null);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RestException(
                'Unable to decode data from JSON' .
                json_last_error_msg()
            );
        }

        return $result;
    }
}