<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.06.16
 * Time: 16:34
 */

namespace zaboy\rest\RqlParser;


use Xiag\Rql\Parser\ExpressionParser;
use Xiag\Rql\Parser\Lexer;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Parser;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\TokenParser;
use Xiag\Rql\Parser\TokenParserGroup;
use Xiag\Rql\Parser\TypeCaster;
use zaboy\rest\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use zaboy\rest\DataStore\ConditionBuilder\RqlConditionBuilder;
use zaboy\rest\DataStore\DataStoreAbstract;

class RqlParser
{
    private $allowedAggregateFunction;
    private $conditionBuilder;

    public static function rqlDecode($rqlQueryString)
    {
        $parser = new self();
        $rql = $parser->decode($rqlQueryString);
        unset($parser);
        return $rql;
    }
    public static function rqlEncode(Query $query)
    {
        $parser = new self();
        $rql = $parser->encode($query);
        unset($parser);
        return $rql;
    }

    public function __construct(array $allowedAggregateFunction = null, ConditionBuilderAbstract $conditionBuilder = null)
    {
        if (isset($allowedAggregateFunction)) {
            $this->allowedAggregateFunction = $allowedAggregateFunction;
        } else {
            $this->allowedAggregateFunction = ['count', 'max', 'min'];
        }

        if (isset($conditionBuilder)) {
            $this->conditionBuilder = $conditionBuilder;
        } else {
            $this->conditionBuilder = new RqlConditionBuilder();
        }
    }

    public function decode($rqlQueryString)
    {
        $queryTokenParser = new TokenParserGroup();
        $queryTokenParser
            ->addTokenParser(new TokenParser\Query\GroupTokenParser($queryTokenParser))
            ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\AndTokenParser($queryTokenParser))
            ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\OrTokenParser($queryTokenParser))
            ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\NotTokenParser($queryTokenParser))
            ->addTokenParser(new TokenParser\Query\Basic\ArrayOperator\InTokenParser())
            ->addTokenParser(new TokenParser\Query\Basic\ArrayOperator\OutTokenParser())
            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\EqTokenParser())
            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\NeTokenParser())
            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LtTokenParser())
            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\GtTokenParser())
            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LeTokenParser())
            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\GeTokenParser())
            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LikeTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ArrayOperator\InTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ArrayOperator\OutTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\EqTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\NeTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LtTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\GtTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LeTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\GeTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LikeTokenParser());


        $parser = (new Parser((new ExpressionParser())
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

    public function encode(Query $query)
    {
        $conditionBuilder = $this->conditionBuilder;
        $rqlQueryString = $conditionBuilder($query->getQuery());
        $rqlQueryString = $rqlQueryString . $this->makeLimit($query);
        $rqlQueryString = $rqlQueryString . $this->makeSort($query);
        $rqlQueryString = $rqlQueryString . $this->makeSelect($query);
        return ltrim($rqlQueryString, '&');
    }

    protected function makeLimit(Query $query)
    {
        $limitNode = $query->getLimit();
        $limit = !$limitNode ? DataStoreAbstract::LIMIT_INFINITY : $limitNode->getLimit();
        $offset = !$limitNode ? 0 : $limitNode->getOffset();
        if ($limit == DataStoreAbstract::LIMIT_INFINITY && $offset == 0) {
            return '';
        } else {
            return sprintf('&limit(%s,%s)', $limit, $offset);
        }
    }

    protected function makeSort(Query $query)
    {
        $sortNode = $query->getSort();
        $sortFilds = !$sortNode ? [] : $sortNode->getFields();
        if (empty($sortFilds)) {
            return '';
        } else {
            $strSort = '';
            foreach ($sortFilds as $key => $value) {
                $prefix = $value == SortNode::SORT_DESC ? '-' : '+';
                $strSort = $strSort . $prefix . $key . ',';
            }
            return '&sort(' . rtrim($strSort, ',') . ')';
        }
    }

    protected function makeSelect(Query $query)
    {
        $selectNode = $query->getSelect();  //What filds will be return
        $selectFilds = !$selectNode ? [] : $selectNode->getFields();
        if (empty($selectFilds)) {
            return '';
        } else {
            $selectString = '&select(';
            foreach ($selectFilds as $fild) {
                $selectString = $selectString . $fild . ',';
            }
            return rtrim($selectString, ',') . ')';
        }
    }
}
