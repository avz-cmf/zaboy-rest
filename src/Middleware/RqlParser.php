<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @see https://github.com/SitePen/dstore/blob/21129125823a29c6c18533e7b5a31432cf6e5c56/src/Request.js
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xiag\Rql\Parser\Lexer;
use Xiag\Rql\Parser\Parser;
use Zend\Stratigility\MiddlewareInterface;

/**
 * Parse RQL query string to Xiag\Rql\Parser\Query object
 *
 * @category   Rest
 * @package    Rest
 */
class RqlParser implements MiddlewareInterface
{

    /**
     *
     * @var Xiag\Rql\Parser\Lexer
     */
    protected $lexer;

    /**
     *
     * @var Xiag\Rql\Parser\ExpressionParser;
     */
    protected $parser;

    public function __construct()
    {
        $this->lexer = new Lexer();
        /*
          $queryTokenParser = new TokenParserGroup();

          @var $queryTokenParser Xiag\Rql\Parser\TokenParserGroup
          $queryTokenParser
          ->addTokenParser(new GroupTokenParser($queryTokenParser))
          ->addTokenParser(new Basic\LogicOperator\AndTokenParser())
          ->addTokenParser(new Basic\ArrayOperator\InTokenParser())
          ->addTokenParser(new Basic\ArrayOperator\OutTokenParser())
          ->addTokenParser(new Fiql\ScalarOperator\EqTokenParser())
          ->addTokenParser(new Basic\ScalarOperator\EqTokenParser())
          ->addTokenParser(new Basic\ScalarOperator\NeTokenParser())
          ->addTokenParser(new Basic\ScalarOperator\LtTokenParser())
          ->addTokenParser(new Basic\ScalarOperator\GtTokenParser())
          ->addTokenParser(new Basic\ScalarOperator\LeTokenParser())
          ->addTokenParser(new Basic\ScalarOperator\GeTokenParser())
          ->addTokenParser(new LimitTokenParser())
          ->addTokenParser(new SortTokenParser())
          ->addTokenParser(new SelectTokenParser());

          $this->parser = new Parser(new ExpressionParser());
          $this->parser->addTokenParser($queryTokenParser); */

        $this->parser = Parser::createDefault();
    }

    /**
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     * @todo if($key = 'select-param') {'select-param': 'prop1,prop2'}
     * @todo chenge 'id' to 'Primary-Key-Value'
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $rqlQueryString = $request->getUri()->getQuery();
        $rqlQueryString = rtrim($rqlQueryString, '&XDEBUG_SESSION_START=netbeans-xdebug');
        $tokens = $this->lexer->tokenize($rqlQueryString); //$tokens = $this->lexer->tokenize($rqlQueryString);//
        /* @var $rqlQueryObject \Xiag\Rql\Parser\Query */
        $rqlQueryObject = $this->parser->parse($tokens);
        $request = $request->withAttribute('Rql-Query-Object', $rqlQueryObject);
        if ($next) {
            return $next($request, $response);
        }
        return $response;
    }

}
