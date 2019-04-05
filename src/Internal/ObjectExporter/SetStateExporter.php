<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\Internal\ObjectExporter;

/**
 * Handles instances of classes with a __set_state() method.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
class SetStateExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     */
    public function supports($object, \ReflectionObject $reflectionObject) : bool
    {
        if ($reflectionObject->hasMethod('__set_state')) {
            $method = $reflectionObject->getMethod('__set_state');

            return $method->isPublic() && $method->isStatic();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, \ReflectionObject $reflectionObject) : array
    {
        $className = $reflectionObject->getName();

        $vars = $this->getObjectVars($object, $this->exporter->skipDynamicProperties
            ? new \ReflectionClass($className) // properties from class definition only
            : $reflectionObject                // properties from class definition + dynamic properties
        );

        $exportedVars = $this->exporter->exportArray($vars);
        $exportedVars = $this->exporter->wrap($exportedVars, '\\' . $className . '::__set_state(',  ')');

        return $exportedVars;
    }

    /**
     * Returns public and private object properties, as an associative array.
     *
     * This is unlike get_object_vars(), which only returns properties accessible from the current scope.
     *
     * The returned values are in line with those returned by var_export() in the array passed to __set_state(); unlike
     * var_export() however, this method throws an exception if the object has overridden private properties, as this
     * would result in a conflict in array keys. In this case, var_export() would return multiple values in the output,
     * which once executed would yield an array containing only the last value for this key in the output.
     *
     * This way we offer a better safety guarantee, while staying compatible with var_export() in the output.
     *
     * @param object           $object          The object to dump.
     * @param \ReflectionClass $reflectionClass A ReflectionClass or ReflectionObject instance for the object.
     *
     * @return array An associative array of property name to value.
     *
     * @throws ExportException
     */
    private function getObjectVars($object, \ReflectionClass $reflectionClass) : array
    {
        $current = $reflectionClass;
        $isParentClass = false;

        $result = [];

        while ($current) {
            foreach ($current->getProperties() as $property) {
                if ($isParentClass && ! $property->isPrivate()) {
                    // property already handled in the child class.
                    continue;
                }

                $name = $property->getName();

                if (array_key_exists($name, $result)) {
                    throw new ExportException(
                        'Class "' . $reflectionClass->getName() . '" has overridden private properties. ' .
                        'This is not supported for exporting objects with __set_state().'
                    );
                }

                $property->setAccessible(true);
                $result[$name] = $property->getValue($object);
            }

            $current = $current->getParentClass();
            $isParentClass = true;
        }

        return $result;
    }
}
