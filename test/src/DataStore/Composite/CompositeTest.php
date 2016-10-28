<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:35 PM
 */

namespace zaboy\test\rest\DataStore\Composite;


use Interop\Container\ContainerInterface;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\Composite\Composite;

class CompositeTest extends \PHPUnit_Framework_TestCase
{

    /** @var  Composite */
    protected $object;

    /** @var  ContainerInterface */
    protected $container;

    protected function setUp()
    {
        $this->container = include 'config/container.php';
    }

    public function test__query_images()
    {
        $this->object = $this->container->get('images');
        $result = $this->object->query(new Query());
        $this->assertEquals([
            ["id" => "21", "image" => "icon1.jpg", "product_id" => "11"],
            ["id" => "22", "image" => "icon2.jpg", "product_id" => "12"],
            ["id" => "23", "image" => "icon3.jpg", "product_id" => "11"],
            ["id" => "24", "image" => "icon4.jpg", "product_id" => "13"],
            ["id" => "26", "image" => "icon5.jpg", "product_id" => "14"],
            ["id" => "27", "image" => "icon6.jpg", "product_id" => "12"],
            ["id" => "28", "image" => "icon7.jpg", "product_id" => "14"],
            ["id" => "29", "image" => "icon8.jpg", "product_id" => "14"],
        ], $result);
    }


    public function test_query_images_SelectProduct()
    {
        $this->object = $this->container->get('images');
        $query = new Query();
        $query->setSelect(new SelectNode(['product.']));
        $result = $this->object->query($query);
        $this->assertEquals([
            ["id" => "21", "image" => "icon1.jpg", "title" => "Edelweiss", "price" => "200"],
            ["id" => "22", "image" => "icon2.jpg", "title" => "Rose", "price" => "50"],
            ["id" => "23", "image" => "icon3.jpg", "title" => "Edelweiss", "price" => "200"],
            ["id" => "24", "image" => "icon4.jpg", "title" => "Queen Rose", "price" => "100"],
            ["id" => "26", "image" => "icon5.jpg", "title" => "King Rose", "price" => "100"],
            ["id" => "27", "image" => "icon6.jpg", "title" => "Rose", "price" => "50"],
            ["id" => "28", "image" => "icon7.jpg", "title" => "King Rose", "price" => "100"],
            ["id" => "29", "image" => "icon8.jpg", "title" => "King Rose", "price" => "100"],
        ], $result);
    }
}
