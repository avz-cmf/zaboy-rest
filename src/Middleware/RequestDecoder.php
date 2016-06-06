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
use Xiag\Rql\Parser\ExpressionParser;
use Xiag\Rql\Parser\Parser;
use Xiag\Rql\Parser\Lexer;
use Xiag\Rql\Parser\TokenParser\Query;
use Xiag\Rql\Parser\TokenParserGroup;
use Xiag\Rql\Parser\TokenParser;
use Xiag\Rql\Parser\TypeCaster;
use zaboy\rest\RestException;
use zaboy\rest\RqlParser\SelectTokenParser;
use Zend\Stratigility\MiddlewareInterface;


/**
 * Parse body fron JSON and add result array to $request->withParsedBody()
 *
 * <b>Used request attributes: </b>
 * <ul>
 * <li>Overwrite-Mode</li>
 * <li>Put-Default-Position</li>
 * <li>Put-Before</li>
 * <li>Rql-Query-Object</li>*
 * </ul>
 *
 * @category   rest
 * @package    zaboy
 */
class RequestDecoder implements MiddlewareInterface
{

    private $allowedAggregateFunction = ['count', 'sum', 'avg', 'min', 'max'];


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
        $overwriteMode = isset($overwriteModeHeader[0]) && $overwriteModeHeader[0] === '*' ? true : false;
        $request = $request->withAttribute('Overwrite-Mode', $overwriteMode);

        $putDefaultPosition = $request->getHeader('Put-Default-Position'); //'start' : 'end'
        if (isset($putDefaultPosition)) {
            $request = $request->withAttribute('Put-Default-Position', $putDefaultPosition);
        }
        // @see https://github.com/SitePen/dstore/issues/42
        $putBeforeHeader = $request->getHeader('Put-Before');
        $putBefore = !empty($putBeforeHeader);
        $request = $request->withAttribute('Put-Before', $putBefore);

        $rqlQueryStringWithXdebug = $request->getUri()->getQuery();
        
        $rqlQueryString = rtrim($rqlQueryStringWithXdebug, '&XDEBUG_SESSION_START=netbeans-xdebug');
        $rqlQueryObject = $this->parsRql($rqlQueryString);
        $request = $request->withAttribute('Rql-Query-Object', $rqlQueryObject);

        
        $contenttype = $request->getHeader('Content-Type');
        if (isset($contenttype[0]) && false !== strpos($contenttype[0], 'json')) {
            $body = $this->jsonDecode($request->getBody()->__toString());
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
        $result = json_decode($data, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RestException(
            'Unable to decode data from JSON' .
            json_last_error_msg()
            );
        }
        json_encode(null);

        return $result;
    }

    /**
     *
     * @param string $rqlQueryString
     * @return type
     */
    public function parsRql($rqlQueryString)
    {
        /*
        $parser = Parser\Parser::createDefault();
        $tokens = $lexer->tokenize($rqlQueryString);
        * @var $rqlQueryObject \Xiag\Rql\Parser\Query *
        $rqlQueryObject = $parser->parse($tokens);*/

        $queryTokenParser = new TokenParserGroup();
        $queryTokenParser
            ->addTokenParser(new Query\GroupTokenParser($queryTokenParser))
            ->addTokenParser(new Query\Basic\LogicOperator\AndTokenParser($queryTokenParser))
            ->addTokenParser(new Query\Basic\LogicOperator\OrTokenParser($queryTokenParser))
            ->addTokenParser(new Query\Basic\LogicOperator\NotTokenParser($queryTokenParser))

            ->addTokenParser(new Query\Basic\ArrayOperator\InTokenParser())
            ->addTokenParser(new Query\Basic\ArrayOperator\OutTokenParser())

            ->addTokenParser(new Query\Basic\ScalarOperator\EqTokenParser())
            ->addTokenParser(new Query\Basic\ScalarOperator\NeTokenParser())
            ->addTokenParser(new Query\Basic\ScalarOperator\LtTokenParser())
            ->addTokenParser(new Query\Basic\ScalarOperator\GtTokenParser())
            ->addTokenParser(new Query\Basic\ScalarOperator\LeTokenParser())
            ->addTokenParser(new Query\Basic\ScalarOperator\GeTokenParser())
            ->addTokenParser(new Query\Basic\ScalarOperator\LikeTokenParser())

            ->addTokenParser(new Query\Fiql\ArrayOperator\InTokenParser())
            ->addTokenParser(new Query\Fiql\ArrayOperator\OutTokenParser())

            ->addTokenParser(new Query\Fiql\ScalarOperator\EqTokenParser())
            ->addTokenParser(new Query\Fiql\ScalarOperator\NeTokenParser())
            ->addTokenParser(new Query\Fiql\ScalarOperator\LtTokenParser())
            ->addTokenParser(new Query\Fiql\ScalarOperator\GtTokenParser())
            ->addTokenParser(new Query\Fiql\ScalarOperator\LeTokenParser())
            ->addTokenParser(new Query\Fiql\ScalarOperator\GeTokenParser())
            ->addTokenParser(new Query\Fiql\ScalarOperator\LikeTokenParser());


        $parser = (new Parser( (new ExpressionParser())
            ->registerTypeCaster('string', new TypeCaster\StringTypeCaster())
            ->registerTypeCaster('integer', new TypeCaster\IntegerTypeCaster())
            ->registerTypeCaster('float', new TypeCaster\FloatTypeCaster())
            ->registerTypeCaster('boolean', new TypeCaster\BooleanTypeCaster())
        ))
            ->addTokenParser(new SelectTokenParser($this->allowedAggregateFunction))
            ->addTokenParser($queryTokenParser)
            ->addTokenParser(new TokenParser\SortTokenParser())
            ->addTokenParser(new TokenParser\LimitTokenParser());

        $rqlQueryObject = $parser->parse((new Lexer())->tokenize($rqlQueryString));

        return $rqlQueryObject;
    }

}
