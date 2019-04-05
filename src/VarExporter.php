<?php

declare(strict_types=1);

namespace Brick\VarExporter;

use Brick\VarExporter\Internal\GenericExporter;

final class VarExporter
{
    /**
     * @var GenericExporter
     */
    protected $exporter;

    /**
     * VarExporter constructor.
     *
     * @param bool $allowReflection Whether to allow classes with a constructor or non-public properties to be exported
     *                              using reflection. Disabled by default. Note that even when this is false, reflection
     *                              may still used to create an empty instance for __unserialize(), but is never used to
     *                              bypass a constructor in another context, or set non-public properties.
     */
    public function __construct(bool $allowReflection = false)
    {
        $this->exporter = new GenericExporter($allowReflection);
    }

    /**
     * @param mixed $var       The variable to export.
     * @param bool  $addReturn Whether to prepend the output with 'return ' and append a semicolon and a newline.
     *                         This makes the code ready to be executed in a PHP file - or eval(), for that matter.
     * @return string
     *
     * @throws ExportException
     */
    public function export($var, bool $addReturn = false) : string
    {
        $lines = $this->exporter->export($var);
        $export = implode(PHP_EOL, $lines);

        if ($addReturn) {
            return 'return ' . $export . ';' .  PHP_EOL;
        }

        return $export;
    }
}
