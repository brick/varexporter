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
        self::assertSame($expected, $exported);

        // test that the exported value is valid PHP, and equal (by value) to the original var;
        // first of all wrap the output in a return statement, only if this was not already requested

        if (0 === ($options & VarExporter::ADD_RETURN)) {
            $exported = 'return ' . $exported . ';';
        }

        $exportedVar = eval($exported);
        $this->assertEquals($var, $exportedVar);
    }

    /**
     * Asserts that export() throws for the given variable.
     *
     * @param string $expectedMessage The expected exception message.
     * @param mixed  $var             The variable to export.
     * @param int    $options         The options to pass to export().
     *
     * @return void
     */
    public function assertExportThrows(string $expectedMessage, $var, int $options = 0) : void
    {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage($expectedMessage);

        $exporter = new VarExporter();
        $exporter->export($var, $options);
    }
}
