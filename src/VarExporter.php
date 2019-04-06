<?php

declare(strict_types=1);

namespace Brick\VarExporter;

use Brick\VarExporter\Internal\GenericExporter;

final class VarExporter
{
    /**
     * Prepends the output with `return ` and append a semicolon and a newline.
     * This makes the code ready to be executed in a PHP file―or eval(), for that matter.
     */
    public const ADD_RETURN = 1 << 0;

    /**
     * Add type hints to bound closures when exporting custom objects through direct property access.
     * This allows the code to be statically analyzed by external tools and IDEs.
     */
    public const ADD_TYPE_HINTS = 1 << 1;

    /**
     * Skips dynamic properties on custom classes in the output. By default, any dynamic property set on a custom class
     * is exported; if this flag is set, dynamic properties are only allowed on stdClass objects, and ignored on other
     * objects.
     */
    public const SKIP_DYNAMIC_PROPERTIES = 1 << 2;

    /**
     * @param mixed $var     The variable to export.
     * @param int   $options A bitmask of options. Possible values are `VarExporter::*` constants.
     *                       Combine multiple options with a bitwise OR `|` operator.
     *
     * @return string
     *
     * @throws ExportException
     */
    public static function export($var, int $options = 0) : string
    {
        $addReturn = (bool) ($options & self::ADD_RETURN);

        $exporter = new GenericExporter($options);

        $lines = $exporter->export($var);
        $export = implode(PHP_EOL, $lines);

        if ($addReturn) {
            return 'return ' . $export . ';' .  PHP_EOL;
        }

        return $export;
    }
}
