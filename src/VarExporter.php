<?php

declare(strict_types=1);

namespace Brick\VarExporter;

class VarExporter
{
    /**
     * @param mixed $var A variable to export.
     *
     * @return string
     *
     * @throws ExportException
     */
    public static function export($var) : string
    {
        return self::doExport($var, 0);
    }

    /**
     * @param mixed $var
     * @param int   $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    private static function doExport($var, int $nestingLevel) : string
    {
        switch ($type = gettype($var)) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
                return var_export($var, true);

            case 'NULL':
                // lowercase null
                return 'null';

            case 'array':
                return self::exportArray($var, $nestingLevel);

            case 'object':
                return self::exportObject($var, $nestingLevel);

            default:
                throw new ExportException(sprintf('Type "%s" is not supported.', $type));
        }
    }

    /**
     * @param array $array
     * @param int   $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    private static function exportArray(array $array, int $nestingLevel) : string
    {
        if (! $array) {
            return '[]';
        }

        $result = '[' . PHP_EOL;

        $isNumeric = array_keys($array) === range(0, count($array) - 1);

        $count = count($array);
        $current = 0;

        foreach ($array as $key => $value) {
            $isLast = (++$current === $count);
            $result .= self::indent($nestingLevel + 1);

            if (! $isNumeric) {
                $result .= var_export($key, true);
                $result .= ' => ';
            }

            $result .= self::doExport($value, $nestingLevel + 1);

            if (! $isLast) {
                $result .= ',';
            }

            $result .= PHP_EOL;
        }

        $result .= self::indent($nestingLevel);
        $result .= ']';

        return $result;
    }

    /**
     * @param object $object
     * @param int    $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    private static function exportObject($object, int $nestingLevel) : string
    {
        if ($object instanceof \stdClass) {
            return '(object) ' . self::exportArray((array) $object, $nestingLevel);
        }

        $values = get_object_vars($object);

        if (! $values) {
            return 'new ' . '\\' . get_class($object);
        }

        $result = '(function() {' . PHP_EOL;
        $result .= self::indent($nestingLevel + 1);
        $result .= '$object = new \\' . get_class($object) . ';' . PHP_EOL;

        foreach ($values as $key => $value) {
            $result .= self::indent($nestingLevel + 1);
            $result .= '$object->' . self::escapeObjectVar($key) . ' = ' . self::doExport($value, $nestingLevel + 1) . ';' . PHP_EOL;
        }

        $result .= PHP_EOL;
        $result .= self::indent($nestingLevel + 1);
        $result .= 'return $object;' . PHP_EOL;

        $result .= self::indent($nestingLevel);
        $result .= ')()';

        return $result;
    }

    /**
     * @param string $var
     *
     * @return string
     */
    private static function escapeObjectVar(string $var) : string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $var) === 1) {
            return $var;
        }

        return '{' . var_export($var, true) . '}';
    }

    /**
     * @param int $nestingLevel
     *
     * @return string
     */
    private static function indent(int $nestingLevel) : string
    {
        return str_repeat(' ', 4 * $nestingLevel);
    }
}
