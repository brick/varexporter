<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\Tests\Classes\NoProperties;
use Brick\VarExporter\Tests\Classes\PrivateConstructor;
use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

/**
 * This function does not exist, but namespace should be taken into account by the closure exporter.
 */
use function Brick\VarExporter\strlen;
use Brick\VarExporter\Tests\Classes\SetState;
use Brick\VarExporter\VarExporter;

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

    public function testExportNamespacedCode()
    {
        $var = function(SetState $a) : NoProperties {
            $a = new PublicPropertiesOnly;
            $b = PrivateConstructor::class;
            $c = function(\PDO $pdo) {
                return \PDO::class;
            };

            substr($b, 0, -7);
            strlen($b);

            return new NoProperties;
        };

        $expected = <<<'PHP'
function (\Brick\VarExporter\Tests\Classes\SetState $a) : \Brick\VarExporter\Tests\Classes\NoProperties {
    $a = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly();
    $b = \Brick\VarExporter\Tests\Classes\PrivateConstructor::class;
    $c = function (\PDO $pdo) {
        return \PDO::class;
    };
    substr($b, 0, -7);
    \Brick\VarExporter\strlen($b);
    return new \Brick\VarExporter\Tests\Classes\NoProperties();
}
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

    public function testExportClosureDefinedInEval()
    {
        $var = eval(<<<PHP
return function() {
    echo 'Hello, world!';
};
PHP
);
        $this->assertExportThrows("Closure defined in eval()'d code cannot be exported.", $var);
    }

    public function testExportClosureDisabled()
    {
        $var = function() {
            echo 'Hello, world!';
        };

        $this->assertExportThrows('Class "Closure" is internal, and cannot be exported.', $var, VarExporter::NO_CLOSURES);
    }
}
