<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\Internal\ObjectExporter;
use Brick\VarExporter\CodeInterface;

/**
 * Handles instances of LiteralInterface.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
final class CodeExporter extends ObjectExporter
{

    public function supports(\ReflectionObject $reflectionObject): bool
    {
        return $this->exporter->allowCode
            && $reflectionObject->implementsInterface(CodeInterface::class);
    }

    public function export(object $object, \ReflectionObject $reflectionObject, array $path, array $parentIds): array
    {
        assert($object instanceof CodeInterface);
        return $object->toLines();
    }

}
