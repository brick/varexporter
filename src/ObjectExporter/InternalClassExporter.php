<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

use Brick\VarExporter\ExportException;
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
    public function supports($object, \ReflectionObject $reflectionObject) : bool
    {
        return $reflectionObject->isInternal();
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, \ReflectionObject $reflectionObject) : array
    {
        $className = $reflectionObject->getName();

        throw new ExportException('Class "' . $className . '" is internal, and cannot be exported.');
    }
}
