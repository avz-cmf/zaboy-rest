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

        $id = $request->getAttribute('id');
        $rqlQueryObject = $request->getAttribute('Rql-Query-Object');
        switch ($request->getMethod()) {
            case 'GET':
                $rowset = $this->dataStore->query($rqlQueryObject);
                $request = $request->withAttribute('Response-Body', $rowset);
                $rowCount = count($request);                
                $limitObject = $rqlQueryObject->getLimit();
                $offset = !$limitObject ? 0 : $limitObject->getOffset(); 
                $contentRange = 'items ' . $offset . '-' . $offset + $rowCount-1;
                $contentRange =  $contentRange . '/' . $rowCount;
                $response = $response->withHeader('Content-Range', $contentRange);
                $response = $response->withStatus(200);    
                break;
            case 'POST':    
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
}