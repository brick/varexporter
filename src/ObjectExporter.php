<?php

declare(strict_types=1);

namespace Brick\VarExporter;

use Brick\VarExporter\Internal\ClassInfo;

/**
 * An exporter that can only handle a particular type of object.
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
     * @param object    $object    The object to export.
     * @param ClassInfo $classInfo A ClassInfo instance for the object's class.
     *
     * @return bool
     */
    abstract public function supports($object, ClassInfo $classInfo) : bool;

    /**
     * Exports the given object.
     *
     * @param object    $object       The object to export.
     * @param ClassInfo $classInfo    A ClassInfo instance for the object's class.
     * @param int       $nestingLevel The current output nesting level.
     *
     * @return string
     *
     * @throws ExportException
     */
    abstract public function export($object, ClassInfo $classInfo, int $nestingLevel) : string;
}
