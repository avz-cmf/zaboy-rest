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
use Zend\Diactoros\Response\JsonResponse;

/**
 * @todo if primary key exist but not in url
 * @category   Rest
 * @package    Rest
 */
class StoreMiddleware extends StoreMiddlewareAbstract 
{
    /**
     *
     * @var ServerRequestInterface 
     */
    protected $request;
    
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
        try {        
            switch ($request->getMethod()) {
                case $httpMethod === 'GET' && $isPrimaryKeyValue:
                    $response = $this->methodGetWithId($request, $response);
                    break;
                case $httpMethod === 'GET' && !($isPrimaryKeyValue):
                    $response = $this->methodGetWithoutId($request, $response);    
                    break;
                case $httpMethod === 'PUT' && $isPrimaryKeyValue:
                    $response = $this->methodPutWithId($request, $response);
                    break;
                case $httpMethod === 'PUT' && !($isPrimaryKeyValue):
                    throw new \zaboy\rest\RestException('PUT without Primary Key');
                case $httpMethod === 'POST' && $isPrimaryKeyValue:
                    $response = $this->methodPostWithId($request, $response);
                    break;
                case $httpMethod === 'POST' && !($isPrimaryKeyValue):
                    $response = $this->methodPostWithoutId($request, $response);
                    break;
                case $httpMethod === 'DELETE':
                    $response = $this->methodDelete($request, $response);
                    break;
                case $httpMethod === 'DELETE' && !($isPrimaryKeyValue):
                    throw new \zaboy\rest\RestException('DELETE without Primary Key');
                default :    
                    throw new \zaboy\rest\RestException(
                       'Method must be GET, PUT, POST or DELETE. '
                       . $request->getMethod() . ' given'
                    );
            }
        } catch (\zaboy\rest\RestException $ex) {
            return new JsonResponse([
                $ex->getMessage()
            ], 500);
        }

        if ($next) {
            return $next($this->request, $response);
        }
        return $response;
    }
    
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodGetWithId(ServerRequestInterface $request, ResponseInterface $response)
    {
        $primaryKeyValue = $request->getAttribute('Primary-Key-Value');
        $row = $this->dataStore->read($primaryKeyValue);
        $this->request = $request->withAttribute('Response-Body', $row);
        $rowCount = empty($request) ? 0 : 1;                
        $contentRange = 'items ' . $primaryKeyValue . '-' . $primaryKeyValue;
        $response = $response->withHeader('Content-Range', $contentRange);
        $response = $response->withStatus(200);
        return $response;
    }    
    
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodGetWithoutId(ServerRequestInterface $request, ResponseInterface $response)
    {
        $rqlQueryObject = $request->getAttribute('Rql-Query-Object');
        $rowset = $this->dataStore->query($rqlQueryObject);
        $this->request = $request->withAttribute('Response-Body', $rowset);
        $rowCount = count($rowset);                
        $limitObject = $rqlQueryObject->getLimit();
        $offset = !$limitObject ? 0 : $limitObject->getOffset(); 
        $contentRange = 'items ' . $offset . '-' . $offset + $rowCount-1 . '/' . $rowCount;
        $response = $response->withHeader('Content-Range', $contentRange);
        $response = $response->withStatus(200);
        return $response;
    }
    
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodPutWithId(ServerRequestInterface $request, ResponseInterface $response)
    {
        $primaryKeyValue = $request->getAttribute('Primary-Key-Value');
        $primaryKeyIdentifier =  $this->dataStore->getIdentifier();
        $row = $request->getParsedBody();
        if (!(isset($row) && is_array($row))) {
            throw new \zaboy\rest\RestException('No body in PUT request');
        }
        $row = array_merge(array($primaryKeyIdentifier => $primaryKeyValue), $row);
        $overwriteMode = $request->getAttribute('Overwrite-Mode');
        $isIdExist = !empty($this->dataStore->read($primaryKeyValue));
        
        if ($overwriteMode && !$isIdExist) {
            $response = $response->withStatus(201);
        }else{
            $response = $response->withStatus(200);
        }
        $newRow = $this->dataStore->update($row, $overwriteMode);
        $this->request  = $request->withAttribute('Response-Body', $newRow);
        return $response;
    } 
    
    
    /**                                              Location: http://www.example.com/users/4/    
     * http://www.restapitutorial.com/lessons/httpmethods.html
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodPostWithId(ServerRequestInterface $request, ResponseInterface $response)
    {
        $primaryKeyValue = $request->getAttribute('Primary-Key-Value');
        $primaryKeyIdentifier =  $this->dataStore->getIdentifier();
       
        $row = $request->getParsedBody();
        if (!(isset($row) && is_array($row))) {
            throw new \zaboy\rest\RestException('No body in POST request');
        }
       
        $row = array_merge(array($primaryKeyIdentifier => $primaryKeyValue), $row);

        $overwriteMode = $request->getAttribute('Overwrite-Mode');

        $existingRow = $this->dataStore->read($primaryKeyValue);
        
        $isIdExist = !empty($existingRow);
    
        if ($isIdExist) {
            $response = $response->withStatus(200); 
        }else{
            $response = $response->withStatus(201);
            $location = $request->getUri()->getPath();
            $response = $response->withHeader('Location', $location);  
        }
        $insertedPrimaryKeyValue = $this->dataStore->create($row, $overwriteMode);
        $this->request = $request->withAttribute('Response-Body', $insertedPrimaryKeyValue);
        return $response;
    } 
    
        /**                                              Location: http://www.example.com/users/4/    
     * http://www.restapitutorial.com/lessons/httpmethods.html
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodPostWithoutId(ServerRequestInterface $request, ResponseInterface $response)
    {
        $row = $request->getParsedBody();
        if (!(isset($row) && is_array($row))) {
            throw new \zaboy\rest\RestException('No body in POST request');
        }
        $response = $response->withStatus(201);
        $insertedPrimaryKeyValue = $this->dataStore->create($row);
        $this->request = $request->withAttribute('Response-Body', $insertedPrimaryKeyValue);
        $location = $request->getUri()->getPath();
        $response = $response->withHeader('Location', $location . '/' . $insertedPrimaryKeyValue);   
        return $response;
    } 
    
    /**                                              Location: http://www.example.com/users/4/    
     * http://www.restapitutorial.com/lessons/httpmethods.html
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function methodDelete(ServerRequestInterface $request, ResponseInterface $response)
    {
        $primaryKeyValue = $request->getAttribute('Primary-Key-Value');
        $rowCount = $this->dataStore->delete($primaryKeyValue);        
        if ( $rowCount == 0 ) {
            $response = $response->withStatus(204);
            $this->request  = $request->withAttribute('Response-Body', 0);            
        }else{
            $response = $response->withStatus(200);
            $this->request  = $request->withAttribute('Response-Body', 1);  
        }
        return $response;
    } 
}