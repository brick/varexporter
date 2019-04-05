<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

use Brick\VarExporter\Internal\ClassInfo;
use Brick\VarExporter\ObjectExporter;

/**
 * Handles any class through reflection.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
class ReflectionExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     */
    public function supports($object, ClassInfo $classInfo) : bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, ClassInfo $classInfo, int $nestingLevel) : string
    {
        $result  = $this->varExporter->indent($nestingLevel + 1);
        $result .= '$class = new \ReflectionClass(\\' . get_class($object) . '::class);' . PHP_EOL;

        $result .= $this->varExporter->indent($nestingLevel + 1);
        $result .= '$object = $class->newInstanceWithoutConstructor();'. PHP_EOL;

        $current = new \ReflectionObject($object);
        $isParentClass = false;

        while ($current) {
            foreach ($current->getProperties() as $property) {
                if ($isParentClass && ! $property->isPrivate()) {
                    // property already handled in the child class.
                    continue;
                }

                $result .= PHP_EOL;

                $property->setAccessible(true);

                $name = $property->getName();
                $value = $property->getValue($object);

                $exportedValue = $this->varExporter->doExport($value, $nestingLevel + 1);

                if ($property->isPublic()) {
                    // public properties AND dynamic properties
                    $result .= $this->varExporter->indent($nestingLevel + 1);
                    $result .= '$object->' . $this->escapePropName($name) . ' = ' . $exportedValue . ';' . PHP_EOL;
                } else {
                    if ($isParentClass) {
                        $result .= $this->varExporter->indent($nestingLevel + 1);
                        $result .= '$property = new \ReflectionProperty(\\' . $current->getName() . '::class, ' . var_export($name, true) . ');' . PHP_EOL;
                    } else {
                        $result .= $this->varExporter->indent($nestingLevel + 1);
                        $result .= '$property = $class->getProperty(' . var_export($name, true) . ');' . PHP_EOL;
                    }

                    $result .= $this->varExporter->indent($nestingLevel + 1);
                    $result .= '$property->setAccessible(true);' . PHP_EOL;

                    $result .= $this->varExporter->indent($nestingLevel + 1);
                    $result .= '$property->setValue($object, ' . $exportedValue . ');' . PHP_EOL;
                }
            }

            $current = $current->getParentClass();
            $isParentClass = true;
        }

        $result .= PHP_EOL;

        $result .= $this->varExporter->indent($nestingLevel + 1);
        $result .= 'return $object;' . PHP_EOL;

        return $this->wrapInClosure($result, $nestingLevel);
    }
}
