<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\Internal\ObjectExporter;

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

        $className = '\\' . $reflectionObject->getName();

        if ($reflectionObject->getConstructor() !== null) {
            $result[] = '$class = new \ReflectionClass(' . $className . '::class);';

            if ($this->exporter->addTypeHints) {
                $result[] = '';
                $result[] = '/** @var ' . $className . ' $object */';
            }

            $result[] = '$object = $class->newInstanceWithoutConstructor();';
        } else {
            $result[] = '$object = new ' . $className . ';';
        }

        $result[] = '';

        $values = $object->__serialize();

        $exportedValues = $this->exporter->export($values);
        $exportedValues = $this->exporter->wrap($exportedValues, '$object->__unserialize(', ');');

        $result = array_merge($result, $exportedValues);

        $result[] = '';
        $result[] = 'return $object;';

        return $this->wrapInClosure($result);
    }
}
