<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;
use Brick\VarExporter\Tests\Classes\SetState;
use Brick\VarExporter\VarExporter;

class VarExporterTest extends AbstractTestCase
{
    public function testMixedVar()
    {
        $myObject = new PublicPropertiesOnly();
        $myObject->foo = 'hello';
        $myObject->bar = new SetState();
        $myObject->bar->foo = 'SetState.foo';
        $myObject->bar->bar = 'SetState.bar';

        $var = [
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
                        'g' => [[]]
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
        ];

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
                'g' => [
                    []
                ]
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
    'aCustomObject' => (static function() {
        $object = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

        $object->foo = 'hello';
        $object->bar = \Brick\VarExporter\Tests\Classes\SetState::__set_state([
            'baz' => 'defaultValue',
            'foo' => 'SetState.foo',
            'bar' => 'SetState.bar'
        ]);

        return $object;
    })()
]
PHP;

        $this->assertExportEquals($expected, $var);
    }

    public function testExportObjectPropWithSpecialChars()
    {
        $object = new PublicPropertiesOnly;
        $object->{'$ref'} = '#/components/schemas/User';

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

    $object->foo = null;
    $object->bar = null;
    $object->{'$ref'} = '#/components/schemas/User';

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testAddReturn()
    {
        $var = [];
        $expected = 'return [];' . PHP_EOL;

        $this->assertExportEquals($expected, $var, VarExporter::ADD_RETURN);
    }

    public function testInlineNumericScalarArray()
    {
        $var = [
            'one' => ['hello', 'world', 123, true, false, null, 7.5],
            'two' => ['hello', 'world', ['one', 'two', 'three']]
        ];

        $expected = <<<'PHP'
[
    'one' => ['hello', 'world', 123, true, false, null, 7.5],
    'two' => [
        'hello',
        'world',
        ['one', 'two', 'three']
    ]
]
PHP;

        $this->assertExportEquals($expected, $var, VarExporter::INLINE_NUMERIC_SCALAR_ARRAY);
    }

    public function testExportInternalClass()
    {
        $object = new \stdClass;
        $object->iterator = new \ArrayIterator();

        $expectedMessage = 'Class "ArrayIterator" is internal, and cannot be exported.';

        $this->assertExportThrows($expectedMessage, $object);
    }

    public function testExportResource()
    {
        $handle = fopen('php://memory', 'rb+');

        // bury it deep
        $object = (object) [
            'foo' => (object) [
                'bar' => $handle
            ]
        ];

        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('At [foo][bar]: Type "resource" is not supported.');

        VarExporter::export($object);
    }

    public function testExportObjectTwiceWithoutCircularReference()
    {
        $a = new PublicPropertiesOnly;
        $a->foo = 'Foo';
        $a->bar = 'Bar';

        $var = [
            'x' => $a,
            'y' => $a
        ];

        $expected = <<<'PHP'
[
    'x' => (static function() {
        $object = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

        $object->foo = 'Foo';
        $object->bar = 'Bar';

        return $object;
    })(),
    'y' => (static function() {
        $object = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

        $object->foo = 'Foo';
        $object->bar = 'Bar';

        return $object;
    })()
]
PHP;

        $this->assertExportEquals($expected, $var);
    }

    public function testExportObjectWithCircularReference()
    {
        $a = new PublicPropertiesOnly;
        $b = new PublicPropertiesOnly;

        $a->foo = $b;
        $b->foo = $a;

        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('At [x][y][foo][foo][foo]: Object of class "Brick\VarExporter\Tests\Classes\PublicPropertiesOnly" has a circular reference at [x][y][foo]');

        VarExporter::export([
            'x' => [
                'y' => $a
            ]
        ]);
    }
}
