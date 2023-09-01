<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\VarExporter;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    /**
     * Asserts the return value of export() for the given variable.
     *
     * @param string $expected The expected export() output.
     * @param mixed  $var      The variable to export.
     * @param int    $options  The options to pass to export().
     *
     * @return void
     */
    public function assertExportEquals(string $expected, $var, int $options = 0) : void
    {
        // test the string output of export()

        $exported = VarExporter::export($var, $options);
        self::assertSame($expected, $exported, 'The export() output is not as expected.');

        // test that the exported value is valid PHP;
        // first of all wrap the output in a return statement, only if this was not already requested

        if (0 === ($options & VarExporter::ADD_RETURN)) {
            $exported = 'return ' . $exported . ';';
        }

        $exportedVar = eval($exported);

        // test that the output is equal (by value) to the original var;
        // only test if SKIP_DYNAMIC_PROPERTIES is not set, as this might create a non-equal object

        if (0 === ($options & VarExporter::SKIP_DYNAMIC_PROPERTIES)) {
            $this->assertEquals($var, $exportedVar, 'The eval()ed exported var is different from the original var.');
        }

        // if the exported value is a closure with no parameters, test that the exported closure returns the same
        // value as the original closure

        if ($var instanceof \Closure) {
            if ((new \ReflectionFunction($var))->getNumberOfRequiredParameters() === 0) {
                $this->assertSame($var(), ($exportedVar()), 'The exported closure does not return the same value as the original closure.');
            }
        }
    }

    /**
     * Asserts that export() throws for the given variable.
     *
     * @param string $expectedMessage The expected exception message. Can use '*' as a placeholder.
     * @param mixed  $var             The variable to export.
     * @param int    $options         The options to pass to export().
     *
     * @return void
     */
    public function assertExportThrows(string $expectedMessage, $var, int $options = 0) : void
    {
        $expectedMessageRegExp = '/' . implode('.*', array_map(fn(string $str) => preg_quote($str, '/'), explode('*', $expectedMessage))) . '/';

        $this->expectException(ExportException::class);
        $this->expectExceptionMessageMatches($expectedMessageRegExp);

        VarExporter::export($var, $options);
    }
}
