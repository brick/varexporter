<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\Internal\ObjectExporter;

/**
 * Handles any class through direct property access and bound closures.
 *
 * @todo On PHP 7.4, we could remove unset() calls from the output for typed properties having no default value.
 *       This doesn't hurt in the meantime.
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

        $returnNewObject = true;

        if ($reflectionObject->getConstructor() !== null) {
            $result[] = '$class = new \ReflectionClass(' . $className . '::class);';
            $result[] = '$object = $class->newInstanceWithoutConstructor();';

            $returnNewObject = false;
        } else {
            $result[] = '$object = new ' . $className . ';';
        }

        $objectAsArray = (array) $object;

        $current = $this->exporter->skipDynamicProperties
            ? new \ReflectionClass($object) // properties from class definition only
            : $reflectionObject;            // properties from class definition + dynamic properties

        $isParentClass = false;

        while ($current) {
            $publicProperties = [];
            $nonPublicProperties = [];
            $unsetPublicProperties = [];
            $unsetNonPublicProperties = [];

            foreach ($current->getProperties() as $property) {
                if ($isParentClass && ! $property->isPrivate()) {
                    // property already handled in the child class.
                    continue;
                }

                $property->setAccessible(true);

                $name = $property->getName();

                // getting the property value through the object to array cast, and not through reflection, as this is
                // currently the only way to know whether a declared property has been unset - at least before PHP 7.4,
                // which will bring ReflectionProperty::isInitialized().

                $key = $this->getPropertyKey($property);

                if (array_key_exists($key, $objectAsArray)) {
                    $value = $objectAsArray[$key];

                    if ($property->isPublic()) {
                        $publicProperties[$name] = $value;
                    } else {
                        $nonPublicProperties[$name] = $value;
                    }
                } else {
                    if ($property->isPublic()) {
                        $unsetPublicProperties[] = $name;
                    } else {
                        $unsetNonPublicProperties[] = $name;
                    }
                }

                $returnNewObject = false;
            }

            if ($publicProperties || $unsetPublicProperties) {
                $result[] = '';

                foreach ($publicProperties as $name => $value) {
                    $exportedValue = $this->exporter->export($value);
                    $exportedValue = $this->exporter->wrap($exportedValue, '$object->' . $this->escapePropName($name) . ' = ', ';');
                    $result = array_merge($result, $exportedValue);
                }

                foreach ($unsetPublicProperties as $name) {
                    $result[] = 'unset($object->' . $this->escapePropName($name) . ');';
                }
            }

            if ($nonPublicProperties || $unsetNonPublicProperties) {
                $code = [];

                if ($this->exporter->addTypeHints) {
                    $code[] = '/** @var \\' . $current->getName() . ' $this */';
                }

                foreach ($nonPublicProperties as $name => $value) {
                    $exportedValue = $this->exporter->export($value);
                    $exportedValue = $this->exporter->wrap($exportedValue, '$this->' . $this->escapePropName($name) . ' = ', ';');
                    $code = array_merge($code, $exportedValue);
                }

                foreach ($unsetNonPublicProperties as $name) {
                    $code[] = 'unset($this->' . $this->escapePropName($name) . ');';
                }

                $result[] = '';
                $result[] = '(function() {';
                $result = array_merge($result, $this->exporter->indent($code));
                $result[] = '})->bindTo($object, \\' . $current->getName() . '::class)();';
            }

            $current = $current->getParentClass();
            $isParentClass = true;
        }

        if ($returnNewObject) {
            // no constructor, no properties
            return ['new ' . $className];
        }

        $result[] = '';
        $result[] = 'return $object;';

        return $this->wrapInClosure($result);
    }

    /**
     * Returns the key of the given property in the object-to-array cast.
     *
     * @param \ReflectionProperty $property
     *
     * @return string
     */
    private function getPropertyKey(\ReflectionProperty $property) : string
    {
        $name = $property->getName();

        if ($property->isPrivate()) {
            return "\0" . $property->getDeclaringClass()->getName() . "\0" . $name;
        }

        if ($property->isProtected()) {
            return "\0*\0" . $name;
        }

        return $name;
    }
}
