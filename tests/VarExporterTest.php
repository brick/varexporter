<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\VarExporter;
use PHPUnit\Framework\TestCase;

class VarExporterTest extends TestCase
{
    public function testMixedVar()
    {
        $myObject = new MyClass;
        $myObject->foo = 'hello';
        $myObject->bar = 'world';

        $exporter = new VarExporter();

        $result = $exporter->export([
            'aString' => 'Hello',
            'aTrue' => true,
            'aFalse' => false,
            'aNull' => null,
            'aFloat' => 0.75,
            'anInt' => 123,
            'aNumericArray' => ['a', 'b', null, [
                'c' => 'd'
            ]],
            'anAssociativeArray' => [
                'a' => 'b',
                'c' => [
                    'd' => 'e',
                    'f' => [
                        'g' => 'h'
                    ]
                ]
            ],
            'anObject' => (object) [
                'type' => 'string',
                '$ref' => '#/components/schema/User',
                'items' => (object) [
                    'foo' => 'bar',
                    'empty' => (object) []
                ]
            ],
            'aCustomObject' => $myObject
        ]);

        $expected = <<<'PHP'
[
    'aString' => 'Hello',
    'aTrue' => true,
    'aFalse' => false,
    'aNull' => null,
    'aFloat' => 0.75,
    'anInt' => 123,
    'aNumericArray' => [
        'a',
        'b',
        null,
        [
            'c' => 'd'
        ]
    ],
    'anAssociativeArray' => [
        'a' => 'b',
        'c' => [
            'd' => 'e',
            'f' => [
                'g' => 'h'
            ]
        ]
    ],
    'anObject' => (object) [
        'type' => 'string',
        '$ref' => '#/components/schema/User',
        'items' => (object) [
            'foo' => 'bar',
            'empty' => (object) []
        ]
    ],
    'aCustomObject' => (function() {
        $object = new \Brick\VarExporter\Tests\MyClass;
        $object->foo = 'hello';
        $object->bar = 'world';

        return $object;
    })()
]
PHP;

        $this->assertSame($expected, $result);
    }

    public function testExportClassWithSetState()
    {
        $exporter = new VarExporter();
        $result = $exporter->export(new SetStateClass());

        $expected = <<<'PHP'
\Brick\VarExporter\Tests\SetStateClass::__set_state([
    'a' => 1,
    'b' => 2,
    'c' => [
        3,
        4
    ]
])
PHP;

        $this->assertSame($expected, $result);
    }

    public function testExportClassWithPrivateConstructor()
    {
        $object = MyClassWithPrivateConstructor::create();
        $object->foo = 'Hello';
        $object->bar = 'World';

        $exporter = new VarExporter();
        $result = $exporter->export($object);

        $expected = <<<'PHP'
(function() {
    $object = (new ReflectionClass(\Brick\VarExporter\Tests\MyClassWithPrivateConstructor::class))->newInstanceWithoutConstructor();
    $object->foo = 'Hello';
    $object->bar = 'World';

    return $object;
})()
PHP;

        $this->assertSame($expected, $result);
    }
}

class MyClass {
    public $foo;
    public $bar;
}

class MyClassWithPrivateConstructor extends MyClass {
    private function __construct()
    {
    }

    public static function create()
    {
        return new self;
    }
}

class SetStateClass {
    private $a = 1;
    protected $b = 2;
    public $c = [3, 4];

    public static function __set_state($state)
    {
    }
}
