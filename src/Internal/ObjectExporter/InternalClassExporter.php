<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\Internal\ObjectExporter;
use Override;

/**
 * Throws on internal classes.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
final class InternalClassExporter extends ObjectExporter
{
    #[Override]
    public function supports(\ReflectionObject $reflectionObject) : bool
    {
        return $reflectionObject->isInternal();
    }

    #[Override]
    public function export(object $object, \ReflectionObject $reflectionObject, array $path, array $parentIds) : array
    {
        $className = $reflectionObject->getName();

        throw new ExportException('Class "' . $className . '" is internal, and cannot be exported.', $path);
    }
}
