<?php

namespace SuperClosure\Test\Visitor;

use SuperClosure\ClosureParser\Ast\Visitor\ClosureLocatorVisitor;

/**
 * @covers SuperClosure\ClosureParser\Ast\Visitor\ClosureLocatorVisitor
 */
class ClosureLocatorVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testClosureNodeIsDiscoveredByVisitor()
    {
        $closure = function () {}; $startLine = __LINE__;
        $reflectedClosure = new \ReflectionFunction($closure);
        $closureFinder = new ClosureLocatorVisitor($reflectedClosure);
        $closureNode = new \PHPParser_Node_Expr_Closure(array(), array('startLine' => $startLine));
        $closureFinder->enterNode($closureNode);

        $this->assertSame($closureNode, $closureFinder->getClosureNode());
    }

    public function testClosureNodeIsAmbiguousIfMultipleClosuresOnLine()
    {
        $this->setExpectedException('RuntimeException');

        $closure = function () {}; function () {}; $startLine = __LINE__;
        $closureFinder = new ClosureLocatorVisitor(new \ReflectionFunction($closure));
        $closureFinder->enterNode(new \PHPParser_Node_Expr_Closure(array(), array('startLine' => $startLine)));
        $closureFinder->enterNode(new \PHPParser_Node_Expr_Closure(array(), array('startLine' => $startLine)));
    }

    public function testCalculatesClosureLocation()
    {
        $closure = function () {};
        $closureFinder = new ClosureLocatorVisitor(new \ReflectionFunction($closure));

        $closureFinder->beforeTraverse(array());

        $node = new \PHPParser_Node_Stmt_Namespace(new \PHPParser_Node_Name(array('Foo', 'Bar')));
        $closureFinder->enterNode($node);
        $closureFinder->leaveNode($node);

        $node = new \PHPParser_Node_Stmt_Trait('Fizz');
        $closureFinder->enterNode($node);
        $closureFinder->leaveNode($node);

        $node = new \PHPParser_Node_Stmt_Class('Buzz');
        $closureFinder->enterNode($node);
        $closureFinder->leaveNode($node);

        $closureFinder->afterTraverse(array());

        $actualLocationKeys = array_filter(get_object_vars($closureFinder->getLocation()));
        $expectedLocationKeys = array('directory', 'file', 'function', 'line');
        if (PHP_VERSION_ID >= 50400) {
            array_unshift($expectedLocationKeys, 'class');
        }
        $this->assertEquals($expectedLocationKeys, array_keys($actualLocationKeys));
    }
}