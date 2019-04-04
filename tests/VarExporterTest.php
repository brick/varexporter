<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;
use Brick\VarExporter\Tests\Classes\SetState;
use Brick\VarExporter\VarExporter;
use PHPUnit\Framework\TestCase;

class VarExporterTest extends TestCase
{
    public function testMixedVar()
    {
        $myObject = new PublicPropertiesOnly();
        $myObject->foo = 'hello';
        $myObject->bar = new SetState();
        $myObject->bar->foo = 'SetState.foo';
        $myObject->bar->bar = 'SetState.bar';

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
    'aCustomObject' => (function() {
        $object = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;
        $object->foo = 'hello';
        $object->bar = \Brick\VarExporter\Tests\Classes\SetState::__set_state([
            'foo' => 'SetState.foo',
            'bar' => 'SetState.bar',
            'baz' => 'defaultValue'
        ]);

        return $object;
    })()
]
PHP;

        $this->assertSame($expected, $result);
    }
}
