<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\Internal\ObjectExporter;
use Override;

/**
 * Handles stdClass objects.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
final class StdClassExporter extends ObjectExporter
{
    #[Override]
    public function supports(\ReflectionObject $reflectionObject) : bool
    {
        return $reflectionObject->getName() === \stdClass::class;
    }

    #[Override]
    public function export(object $object, \ReflectionObject $reflectionObject, array $path, array $parentIds) : array
    {
        $exported = $this->exporter->exportArray((array) $object, $path, $parentIds);

        $exported[0] = '(object) ' . $exported[0];

        return $exported;
    }
}
