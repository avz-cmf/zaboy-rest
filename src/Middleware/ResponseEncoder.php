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
use Zend\Diactoros\Response\JsonResponse;

/**
 * Check Accept Header and encode Response to JSON 
 * 
 * Encode Response from $request->getAttribute('Response-Body')
 * 
 * @category   Rest
 * @package    Rest
 */
class ResponseEncoder implements MiddlewareInterface
{
    
    /**
     * 
     * @todo Chenge format of JSON response from [{}] to {} for one row response?
     * @todo Add develope mode for debug with HTML POST and GET
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $responseBody = $request->getAttribute('Response-Body');       
        $accept = $request->getHeaderLine('Accept');
var_dump(preg_match('#^application/([^+\s]+\+)?json#', $accept))  ;      
        if (isset($accept) && preg_match('#^application/([^+\s]+\+)?json#', $accept)) {
            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
var_dump($responseBody);            
            $response = new JsonResponse($responseBody, $status, $headers);
        }else{
            /**
            $response->getBody()->write(json_encode([
                'status' => 405,
                'detail' => 'This API can only provide JSON representations',
            ]));
             * 
             */
        }
        
        if ($next) {
            return $next($request, $response);
        }
        return $response;      
    }
}