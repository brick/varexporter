<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\Tests\Classes\NoProperties;
use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;
use Brick\VarExporter\Tests\Classes\SetState;
use Brick\VarExporter\VarExporter;

/**
 * The function & the const below do not exist, but their namespace should be taken into account by the exporter.
 */
use function Brick\VarExporter\Dummy\Functions\imported_function;
use const Brick\VarExporter\Dummy\Constants\IMPORTED_CONSTANT;

/**
 * Tests exporting closures.
 */
class ExportClosureTest extends AbstractTestCase
{
    public function testExportSimpleClosure(): void
    {
        $var = function() {
            return 'Hello, world!';
        };

        $expected = <<<'PHP'
function () {
    return 'Hello, world!';
}
PHP;

        $this->assertExportEquals($expected, $var);
    }

    public function testExportNestedComplexClosure(): void
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
            return function (\Brick\VarExporter\Tests\Classes\PublicPropertiesOnly $a, int $b, string &$c, string ...$d): ?string {
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

    public function testExportNamespacedCode(): void
    {
        $var = function(SetState $a) : array {
            return [
                'callback' => function(SetState $a) : NoProperties {
                    strlen(PHP_VERSION);
                    imported_function(IMPORTED_CONSTANT);
                    \Brick\VarExporter\Dummy\Functions\explicitly_namespaced_function(\Brick\VarExporter\Dummy\Constants\EXPLICITLY_NAMESPACED_CONSTANT);

                    return new NoProperties();
                }
            ];
        };

        $expected = <<<'PHP'
function (\Brick\VarExporter\Tests\Classes\SetState $a): array {
    return ['callback' => function (\Brick\VarExporter\Tests\Classes\SetState $a): \Brick\VarExporter\Tests\Classes\NoProperties {
        strlen(PHP_VERSION);
        \Brick\VarExporter\Dummy\Functions\imported_function(\Brick\VarExporter\Dummy\Constants\IMPORTED_CONSTANT);
        \Brick\VarExporter\Dummy\Functions\explicitly_namespaced_function(\Brick\VarExporter\Dummy\Constants\EXPLICITLY_NAMESPACED_CONSTANT);
        return new \Brick\VarExporter\Tests\Classes\NoProperties();
    }];
}
PHP;

        $this->assertExportEquals($expected, $var);
    }

    public function testExportClosureWithStringsContainingLikeBreaks(): void
    {
        $var = function() {
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
return function () {
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

PHP;

        $this->assertExportEquals($expected, $var, VarExporter::ADD_RETURN);
    }

    public function testExportClosureWithUse(): void
    {
        $foo = 'bar';

        $var = function() use ($foo) {
            return $foo;
        };

        $this->assertExportThrows(
            "The closure has bound variables through 'use', this is not supported by default. " .
                "Use the CLOSURE_SNAPSHOT_USE option to export them.",
            $var
        );
    }

    public function testExportClosureWithUseAsVars(): void
    {
        $foo = 'b' . 'a' . 'r';

        $var = function() use ($foo) {
            return $foo;
        };

        $expected = <<<'PHP'
return function () {
    $foo = 'bar';
    return $foo;
};

PHP;

        $this->assertExportEquals($expected, $var, VarExporter::ADD_RETURN | VarExporter::CLOSURE_SNAPSHOT_USES);
    }

    public function testExportClosureWithUseClosure(): void
    {
        $foo = 'b' . 'a' . 'r';

        $sub = function () use ($foo) {
            return $foo;
        };

        $var = function() use ($sub) {
            return $sub();
        };

        $expected = <<<'PHP'
return function () {
    $sub = function () {
        $foo = 'bar';
        return $foo;
    };
    return $sub();
};

PHP;

        $this->assertExportEquals($expected, $var, VarExporter::ADD_RETURN | VarExporter::CLOSURE_SNAPSHOT_USES);
    }


    public function testExportArrowFunction(): void
    {
        $var = [fn ($planet) => 'hello ' . $planet]; // Wrapping in array for valid syntax PHP <7.4

        $expected = <<<'PHP'
return [
    function ($planet) {
        return 'hello ' . $planet;
    }
];

PHP;

        $this->assertExportEquals($expected, $var, VarExporter::ADD_RETURN);
    }

    public function testExportArrowFunctionWithContext(): void
    {
        $greet = 'hello';

        $var = [fn ($planet) => $greet . ' ' . $planet];

        $this->assertExportThrows(
            "The arrow function uses variables in the parent scope, this is not supported by default. " .
            "Use the CLOSURE_SNAPSHOT_USE option to export them.",
            $var
        );
    }

    public function testExportArrowFunctionWithContextVarAsVar(): void
    {
        $greet = 'hello';

        $var = [fn ($planet) => $greet . ' ' . $planet];

        $expected = <<<'PHP'
return [
    function ($planet) {
        $greet = 'hello';
        return $greet . ' ' . $planet;
    }
];

PHP;

        $this->assertExportEquals($expected, $var, VarExporter::ADD_RETURN | VarExporter::CLOSURE_SNAPSHOT_USES);
    }

    public function testExportClosureDefinedInEval(): void
    {
        $var = eval(<<<PHP
return function() {
    return 'Hello, world!';
};
PHP
);
        $this->assertExportThrows("Closure defined in eval()'d code cannot be exported.", $var);
    }

    public function testExportTwoClosuresOnSameLine(): void
    {
        $var = function() { return function() {}; };

        $this->assertExportThrows("Expected exactly 1 closure in */tests/ExportClosureTest.php on line *, found 2.", $var);
    }

    public function testExportClosureDisabled(): void
    {
        $var = function() {
            return 'Hello, world!';
        };

        $this->assertExportThrows('Class "Closure" is internal, and cannot be exported.', $var, VarExporter::NO_CLOSURES);
    }
}
