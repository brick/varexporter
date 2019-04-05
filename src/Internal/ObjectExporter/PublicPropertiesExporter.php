<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\Internal\ObjectExporter;

/**
 * Handles instances of classes with public properties only, and no constructor.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
class PublicPropertiesExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     */
    public function supports($object, \ReflectionObject $reflectionObject) : bool
    {
        if ($reflectionObject->getConstructor() !== null) {
            return false;
        }

        for ($currentClass = $reflectionObject; $currentClass; $currentClass = $currentClass->getParentClass()) {
            foreach ($currentClass->getProperties() as $property) {
                if (! $property->isPublic() && ! $property->isStatic()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, \ReflectionObject $reflectionObject) : array
    {
        $newObject = 'new ' . '\\' . $reflectionObject->getName();

        $values = get_object_vars($object);

        if (! $values) {
            return [$newObject];
        }

        if ($this->exporter->skipDynamicProperties) {
            $values = $this->filterOutDynamicProperties($values, $reflectionObject->getName());
        }

        $result = [];

        $result[] = '$object = ' . $newObject . ';';

        foreach ($values as $key => $value) {
            $exportedValue = $this->exporter->export($value);
            $exportedValue = $this->exporter->wrap($exportedValue, '$object->' . $this->escapePropName($key) . ' = ', ';');

            $result = array_merge($result, $exportedValue);
        }

        $result[] = '';
        $result[] = 'return $object;';

        return $this->wrapInClosure($result);
    }

    /**
     * @param array  $values
     * @param string $className
     *
     * @return array
     */
    private function filterOutDynamicProperties(array $values, string $className) : array
    {
        // Using ReflectionClass, which unlike ReflectionObject, only returns the properties defined on the class,
        // and not dynamic properties.
        $reflectionClass = new \ReflectionClass($className);

        $properties = array_map(function(\ReflectionProperty $property) : string {
            return $property->getName();
        }, $reflectionClass->getProperties());

        return array_intersect_key($values, array_flip($properties));
    }
}
