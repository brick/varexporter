<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\Internal\ClassInfo;
use Brick\VarExporter\ObjectExporter;

/**
 * Throws on internal classes.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
class InternalClassExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     */
    public function supports($object, ClassInfo $classInfo) : bool
    {
        return $classInfo->reflectionClass->isInternal();
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, ClassInfo $classInfo, int $nestingLevel) : string
    {
        throw new ExportException('Class "' . get_class($object) . '" is internal, and cannot be exported.');
    }
}
