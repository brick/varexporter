<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\Internal\ClassInfo;
use Brick\VarExporter\ObjectExporter;

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
    public function supports($object, ClassInfo $classInfo) : bool
    {
        if ($classInfo->reflectionClass->hasMethod('__set_state')) {
            $method = $classInfo->reflectionClass->getMethod('__set_state');

            return $method->isPublic() && $method->isStatic();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, ClassInfo $classInfo, int $nestingLevel) : string
    {
        $vars = $this->getObjectVars($object);

        return '\\' . get_class($object) . '::__set_state(' . $this->varExporter->exportArray($vars, $nestingLevel) . ')';
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
     * @param object $object
     *
     * @return array
     *
     * @throws ExportException
     */
    private function getObjectVars($object) : array
    {
        $reflectionObject = new \ReflectionObject($object);

        $current = $reflectionObject;
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
                        'Class "' . $reflectionObject->getName() . '" has overridden private properties. ' .
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
