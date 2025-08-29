<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(__DIR__ . '/vendor/brick/coding-standard/ecs.php');

    $libRootPath = realpath(__DIR__ . '/../../');

    $ecsConfig->paths(
        [
            $libRootPath . '/src',
            $libRootPath . '/tests',
            __FILE__,
        ],
    );

    $ecsConfig->skip([
        // uses unknown functions etc., let's not touch it
        $libRootPath . '/tests/ExportClosureTest.php',

        // tests expect a certain order of class elements
        OrderedClassElementsFixer::class => $libRootPath . '/tests/Classes/Hierarchy/*.php',

        // assertEquals() is used intentionally in assertExportEquals()
        PhpUnitStrictFixer::class => $libRootPath . 'tests/AbstractTestCase.php',
    ]);
};
