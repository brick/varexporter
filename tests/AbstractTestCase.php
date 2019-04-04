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
     *
     * @return void
     */
    public function assertExportEquals(string $expected, $var) : void
    {
        $exporter = new VarExporter();
        $result = $exporter->export($var);

        self::assertSame($expected, $result);
    }

    /**
     * Asserts that export() throws for the given variable.
     *
     * @param string $expectedMessage The expected exception message.
     * @param mixed  $var             The variable to export.
     *
     * @return void
     */
    public function assertExportThrows(string $expectedMessage, $var) : void
    {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage($expectedMessage);

        $exporter = new VarExporter();
        $exporter->export($var);
    }
}
