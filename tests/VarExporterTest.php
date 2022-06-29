<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;
use Brick\VarExporter\Tests\Classes\SetState;
use Brick\VarExporter\VarExporter;

class VarExporterTest extends AbstractTestCase
{
    public function testMixedVar(): void
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

        if (PHP_VERSION_ID >= 80100) {
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
            'foo' => 'SetState.foo',
            'bar' => 'SetState.bar',
            'baz' => 'defaultValue'
        ]);

        return $object;
    })()
]
PHP;
        } else {
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
        }

        $this->assertExportEquals($expected, $var);
    }

    public function testExportObjectPropWithSpecialChars(): void
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

    public function testAddReturn(): void
    {
        $var = [];
        $expected = 'return [];' . PHP_EOL;

        $this->assertExportEquals($expected, $var, VarExporter::ADD_RETURN);
    }

    public function testInlineScalarList(): void
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

        $this->assertExportEquals($expected, $var, VarExporter::INLINE_SCALAR_LIST);
    }

    public function testTrailingCommaInArray(): void
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
        ['one', 'two', 'three'],
    ],
]
PHP;

        $this->assertExportEquals($expected, $var, VarExporter::INLINE_SCALAR_LIST | VarExporter::TRAILING_COMMA_IN_ARRAY);
    }

    public function testExportDateTime(): void
    {
        $timezone = new \DateTimeZone('Europe/Berlin');
        $format = 'Y-m-d H:i:s.u';

        $var = \DateTime::createFromFormat($format, '2020-03-09 18:51:23.000000', $timezone);

        $expected = <<<'PHP'
\DateTime::__set_state([
    'date' => '2020-03-09 18:51:23.000000',
    'timezone_type' => 3,
    'timezone' => 'Europe/Berlin'
])
PHP;

        $this->assertExportEquals($expected, $var);
    }

    public function testExportDateTimeImmutable(): void
    {
        $timezone = new \DateTimeZone('Europe/Berlin');
        $format = 'Y-m-d H:i:s.u';

        $var = \DateTimeImmutable::createFromFormat($format, '2020-03-10 17:06:19.000000', $timezone);

        $expected = <<<'PHP'
\DateTimeImmutable::__set_state([
    'date' => '2020-03-10 17:06:19.000000',
    'timezone_type' => 3,
    'timezone' => 'Europe/Berlin'
])
PHP;

        $this->assertExportEquals($expected, $var);
    }

    public function testExportInternalClass(): void
    {
        $object = new \stdClass;
        $object->iterator = new \ArrayIterator();

        $expectedMessage = 'Class "ArrayIterator" is internal, and cannot be exported.';

        $this->assertExportThrows($expectedMessage, $object);
    }

    public function testExportResource(): void
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

    public function testExportObjectTwiceWithoutCircularReference(): void
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

    public function testExportObjectWithCircularReference(): void
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

    /**
     * @dataProvider providerExportIndented
     *
     * @param mixed $var
     */
    public function testExportIndented($var, string $expected, int $options): void
    {
        $template = <<<'TPL'
public function foo()
{
    $data = {{exported}};
}
TPL;

        $exported = VarExporter::export($var, $options, 1);
        $result = str_replace('{{exported}}', $exported, $template);

        $this->assertEquals($expected, $result);
    }

    public function providerExportIndented(): iterable
    {
        // Array
        $var = ['one' => ['hello', true], 'two' => 2];
        $expected = <<<'PHP'
public function foo()
{
    $data = [
        'one' => [
            'hello',
            true
        ],
        'two' => 2
    ];
}
PHP;
        yield [$var, $expected, 0];

        // Null
        $var = null;
        $expected = <<<'PHP'
public function foo()
{
    $data = null;
}
PHP;
        yield [$var, $expected, 0];

        // Closure
        $var = function () {
            return 'Hello, world!';
        };
        $expected = <<<'PHP'
public function foo()
{
    $data = function () {
        return 'Hello, world!';
    };
}
PHP;
        yield [$var, $expected, 0];

        $foo = 'bar';
        $sub = function () use ($foo) {
            return $foo;
        };
        $var = function () use ($sub) {
            return $sub();
        };

        $expected = <<<'PHP'
public function foo()
{
    $data = function () {
        $sub = function () {
            $foo = 'bar';
            return $foo;
        };
        return $sub();
    };
}
PHP;
        yield [$var, $expected, VarExporter::CLOSURE_SNAPSHOT_USES];

        $var = function () {
            $a = 'Hello,
World!';

            $b = <<<TXT
Hello,
world!
TXT;

            $c = <<<'TXT'
Hello,
world!
TXT;

            return $a . $b . $c;
        };

        $expected = <<<'PHP'
public function foo()
{
    $data = function () {
        $a = 'Hello,
World!';
        $b = <<<TXT
Hello,
world!
TXT;
        $c = <<<'TXT'
Hello,
world!
TXT;
        return $a . $b . $c;
    };
}
PHP;

        yield [$var, $expected, 0];
    }
}
