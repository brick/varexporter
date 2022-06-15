<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\Internal\ObjectExporter;
use UnitEnum;

/**
 * Handles enums.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
class EnumExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     *
     * See: https://github.com/vimeo/psalm/pull/8117
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    public function supports(\ReflectionObject $reflectionObject) : bool
    {
        if (! method_exists($reflectionObject, 'isEnum')) {
            return false;
        }

        return $reflectionObject->isEnum();
    }

    /**
     * {@inheritDoc}
     */
    public function export(object $object, \ReflectionObject $reflectionObject, array $path, array $parentIds) : array
    {
        assert($object instanceof UnitEnum);

        return [
            get_class($object) . '::' . $object->name
        ];
    }
}
