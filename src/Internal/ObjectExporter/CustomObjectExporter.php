<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\Internal\ObjectExporter;

/**
 * Handles any class through direct property access and bound closures.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
class CustomObjectExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     */
    public function supports($object, \ReflectionObject $reflectionObject) : bool
    {
        return true;
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
            $result[] = '$object = $class->newInstanceWithoutConstructor();';
        } else {
            if (! (array) $object) {
                // no constructor, no properties
                return ['new ' . $className];
            }

            $result[] = '$object = new ' . $className . ';';
        }

        $current = $this->exporter->skipDynamicProperties
            ? new \ReflectionClass($object) // properties from class definition only
            : $reflectionObject;            // properties from class definition + dynamic properties

        $isParentClass = false;

        while ($current) {
            $publicProperties = [];
            $nonPublicProperties = [];

            foreach ($current->getProperties() as $property) {
                if ($isParentClass && ! $property->isPrivate()) {
                    // property already handled in the child class.
                    continue;
                }

                $property->setAccessible(true);

                $name = $property->getName();
                $value = $property->getValue($object);

                if ($property->isPublic()) {
                    $publicProperties[$name] = $value;
                } else {
                    $nonPublicProperties[$name] = $value;
                }
            }

            if ($publicProperties) {
                $result[] = '';

                foreach ($publicProperties as $name => $value) {
                    $exportedValue = $this->exporter->export($value);
                    $exportedValue = $this->exporter->wrap($exportedValue, '$object->' . $this->escapePropName($name) . ' = ', ';');
                    $result = array_merge($result, $exportedValue);
                }
            }

            if ($nonPublicProperties) {
                $code = [];

                if ($this->exporter->addTypeHints) {
                    $code[] = '/** @var \\' . $current->getName() . ' $this */';
                }

                foreach ($nonPublicProperties as $name => $value) {
                    $exportedValue = $this->exporter->export($value);
                    $exportedValue = $this->exporter->wrap($exportedValue, '$this->' . $this->escapePropName($name) . ' = ', ';');
                    $code = array_merge($code, $exportedValue);
                }

                $result[] = '';
                $result[] = '(function() {';
                $result = array_merge($result, $this->exporter->indent($code));
                $result[] = '})->bindTo($object, \\' . $current->getName() . '::class)();';
            }

            $current = $current->getParentClass();
            $isParentClass = true;
        }

        $result[] = '';
        $result[] = 'return $object;';

        return $this->wrapInClosure($result);
    }
}
