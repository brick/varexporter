<?php

declare(strict_types=1);

namespace Brick\VarExporter;

/**
 * An exporter that can only handle a particular type of object.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
abstract class ObjectExporter
{
    /**
     * @var VarExporter
     */
    protected $varExporter;

    /**
     * @param VarExporter $varExporter
     */
    public function __construct(VarExporter $varExporter)
    {
        $this->varExporter = $varExporter;
    }

    /**
     * Returns whether this exporter supports the given object.
     *
     * @param object            $object           The object to export.
     * @param \ReflectionObject $reflectionObject A reflection of the object.
     *
     * @return bool
     */
    abstract public function supports($object, \ReflectionObject $reflectionObject) : bool;

    /**
     * Exports the given object.
     *
     * @param object            $object           The object to export.
     * @param \ReflectionObject $reflectionObject A reflection of the object.
     * @param int               $nestingLevel     The current output nesting level.
     *
     * @return string
     *
     * @throws ExportException
     */
    abstract public function export($object, \ReflectionObject $reflectionObject, int $nestingLevel) : string;

    /**
     * Wraps the given PHP code in a static closure.
     *
     * @param string $code
     * @param int    $nestingLevel
     *
     * @return string
     */
    final protected function wrapInClosure(string $code, int $nestingLevel) : string
    {
        $result  = '(static function() {' . PHP_EOL;
        $result .= $code;
        $result .= $this->varExporter->indent($nestingLevel);
        $result .= '})()';

        return $result;
    }

    /**
     * @param string $var
     *
     * @return string
     */
    final protected function escapePropName(string $var) : string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $var) === 1) {
            return $var;
        }

        return '{' . var_export($var, true) . '}';
    }
}
