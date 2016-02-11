<?php
/**
 * Zaboy lib (http://zaboy.org/lib/)
 * 
 * @see http://tools.ietf.org/html/rfc2616#page-122
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Middleware;

use zaboy\res\Middlewares\StoreMiddlewareAbstract;
use zaboy\middleware\MiddlewaresException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xiag\Rql\Parser\Query;

/**
 * 
 * @category   Rest
 * @package    Rest
 */
class StoreMiddleware extends StoreMiddlewareAbstract 
{
    
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $isPrimaryKeyValue = null !== $request->getAttribute('Primary-Key-Value');
        $httpMethod = $request->getMethod();
        switch ($request->getMethod()) {
            case $httpMethod === 'GET' && $isPrimaryKeyValue:
                return $this->methodGetWithId($request, $response, $next);
            case $httpMethod === 'GET' && !($isPrimaryKeyValue):
                return $this->methodGetWithoutId($request, $response, $next);    
            case $httpMethod === 'PUT' && $isPrimaryKeyValue:
                try {
                    $body = $request->getParsedBody();
                    if (!$body || !is_array($body)) {
                        throw new MiddlewaresException('No body in POST request');
                    }
                    /* @var $this->dataStore \zaboy\res\DataStores\DataStoresInterface */
                    $count = $this->dataStore->create($body, true);//$rewriteIfExist = true
                    return $next($req, new JsonResponse([
                        'status' => 'ok',
                        'data' => $data
                    ]));
                } catch (\Exception $ex) {
                    return new JsonResponse([
                        'status' => 'error',
                        'error' => $ex->getMessage()
                    ], 500);
                }
            break;
        }

        if ($next) {
            return $next($request, $response);
        }
        return $response;
    }
    
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodGetWithId(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $primaryKeyValue = $request->getAttribute('Primary-Key-Value');
        $row = $this->dataStore->read($primaryKeyValue);
        $request = $request->withAttribute('Response-Body', $row);
        $rowCount = empty($request) ? 0 : 1;                
        $contentRange = 'items ' . $primaryKeyValue . '-' . $primaryKeyValue;
        $response = $response->withHeader('Content-Range', $contentRange);
        $response = $response->withStatus(200);
        if ($next) {
            return $next($request, $response);
        }
        return $response;
    }    
    
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodGetWithoutId(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $rqlQueryObject = $request->getAttribute('Rql-Query-Object');
        $rowset = $this->dataStore->query($rqlQueryObject);
        $request = $request->withAttribute('Response-Body', $rowset);
        $rowCount = count($rowset);                
        $limitObject = $rqlQueryObject->getLimit();
        $offset = !$limitObject ? 0 : $limitObject->getOffset(); 
        $contentRange = 'items ' . $offset . '-' . $offset + $rowCount-1 . '/' . $rowCount;
        $response = $response->withHeader('Content-Range', $contentRange);
        $response = $response->withStatus(200);
        if ($next) {
            return $next($request, $response);
        }
        return $response;
    }
    
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodPutWithId(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $primaryKeyValue = $request->getAttribute('Primary-Key-Value');
        $primaryKeyIdentifier =  $this->dataStore->getIdentifier();
        $row = $request->getParsedBody();
        if (!(isset($row) && is_array($body))) {
            throw new MiddlewaresException('No body in PUT request');
        }
        $row[$primaryKeyIdentifier] = $primaryKeyValue;
        
        $overwriteMode = $request->getAttribute('Overwrite-Mode');
        $isIdExist = !empty($this->dataStore->read($primaryKeyValue));
        if ($overwriteMode && !$isIdExist) {
            $response = $response->withStatus(201);
        }else{
            $response = $response->withStatus(200);
        }
        
        $newRow = $this->dataStore->update($primaryKeyValue, $overwriteMode);
        $request = $request->withAttribute('Response-Body', $newRow);
        if ($next) {
            return $next($request, $response);
        }
        return $response;
    } 
}