<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

use Brick\VarExporter\ObjectExporter;

/**
 * Handles instances of classes with __serialize() and __unserialize() methods.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
class SerializeExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     */
    public function supports($object, \ReflectionObject $reflectionObject) : bool
    {
        return $reflectionObject->hasMethod('__serialize')
            && $reflectionObject->hasMethod('__unserialize');
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, \ReflectionObject $reflectionObject) : array
    {
        $result = [];

        if ($reflectionObject->getConstructor() !== null) {
            $result[] = '$class = new \ReflectionClass(\\' . get_class($object) . '::class);';
            $result[] = '$object = $class->newInstanceWithoutConstructor();';
        } else {
            $result[] = '$object = new \\' . get_class($object) . ';';
        }

        $result[] = '';

        $values = $object->__serialize();

        $exportedValues = $this->varExporter->doExport($values);
        $exportedValues = $this->varExporter->wrap($exportedValues, '$object->__unserialize(', ');');

        $result = array_merge($result, $exportedValues);

        $result[] = '';
        $result[] = 'return $object;';

        return $this->wrapInClosure($result);
    }
}
