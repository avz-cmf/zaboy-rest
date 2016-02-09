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
use Xiag\Rql\Parser\ExpressionParser;
use Xiag\Rql\Parser\TokenParserGroup;
use Xiag\Rql\Parser\TokenParser\Query\GroupTokenParser;
use Xiag\Rql\Parser\TokenParser\SelectTokenParser;
use Xiag\Rql\Parser\TokenParser\LimitTokenParser;
use Xiag\Rql\Parser\TokenParser\SortTokenParser;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\TokenParser\Query\Fiql;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
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

        $queryTokenParser = new TokenParserGroup();
        /* @var $queryTokenParser Xiag\Rql\Parser\TokenParserGroup */
        $queryTokenParser
            ->addTokenParser(new GroupTokenParser($queryTokenParser))
            ->addTokenParser(new Fiql\ArrayOperator\InTokenParser())
            ->addTokenParser(new Fiql\ArrayOperator\OutTokenParser())
            ->addTokenParser(new Fiql\ScalarOperator\EqTokenParser())
            ->addTokenParser(new Fiql\ScalarOperator\NeTokenParser())
            ->addTokenParser(new Fiql\ScalarOperator\LtTokenParser())
            ->addTokenParser(new Fiql\ScalarOperator\GtTokenParser())
            ->addTokenParser(new Fiql\ScalarOperator\LeTokenParser())
            ->addTokenParser(new Fiql\ScalarOperator\GeTokenParser())
            ->addTokenParser(new LimitTokenParser())
            ->addTokenParser(new SortTokenParser())        
            ->addTokenParser(new SelectTokenParser());       
        
        $this->parser = new Parser(new ExpressionParser());        
        $this->parser->addTokenParser($queryTokenParser);
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
        $tokens = $this->lexer->tokenize($rqlQueryString);
        /* @var $rqlQueryObject \Xiag\Rql\Parser\Query */
        $rqlQueryObject = $this->parser->parse($tokens);

        $request = $request->withAttribute('Rql-Query-Object', $rqlQueryObject);        
        if ($next) {
            return $next($request, $response);
        }
        return $response;
    }
}