<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\Internal\ObjectExporter;
use Override;
use UnitEnum;

/**
 * Handles enums.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
final class EnumExporter extends ObjectExporter
{
    #[Override]
    public function supports(\ReflectionObject $reflectionObject) : bool
    {
        return $reflectionObject->isEnum();
    }

    #[Override]
    public function export(object $object, \ReflectionObject $reflectionObject, array $path, array $parentIds) : array
    {
        assert($object instanceof UnitEnum);

        return [
            $object::class . '::' . $object->name
        ];
    }
}
