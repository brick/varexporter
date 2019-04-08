<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

/**
 * Tests exporting closures.
 */
class ExportClosureTest extends AbstractTestCase
{
    public function testExportSimpleClosure()
    {
        $var = function() {
            echo 'Hello, world!';
        };

        $expected = <<<'PHP'
function () {
    echo 'Hello, world!';
}
PHP;

        $this->assertExportEquals($expected, $var);
    }

    public function testExportNestedComplexClosure()
    {
        $var = [
            (object) [
                'callback' => function() {
                    return function (PublicPropertiesOnly $a, int $b, string & $c, string ...$d) : ?string {
                        $a->foo += $b;
                        $c = $a->bar;
                        $a->bar = implode('', $d);

                        $this->someProp = [
                            $a->foo,
                            $a->bar,
                            $c
                        ];

                        return $this->someProp['c'];
                    };
                }
            ]
        ];

        $expected = <<<'PHP'
[
    (object) [
        'callback' => function () {
            return function (\Brick\VarExporter\Tests\Classes\PublicPropertiesOnly $a, int $b, string &$c, string ...$d) : ?string {
                $a->foo += $b;
                $c = $a->bar;
                $a->bar = implode('', $d);
                $this->someProp = [$a->foo, $a->bar, $c];
                return $this->someProp['c'];
            };
        }
    ]
]
PHP;

        $this->assertExportEquals($expected, $var);
    }

    public function testExportClosureWithUse()
    {
        $foo = 'bar';

        $var = function() use ($foo) {
            return $foo;
        };

        $this->assertExportThrows("The closure has bound variables through 'use', this is not supported.", $var);
    }
}
