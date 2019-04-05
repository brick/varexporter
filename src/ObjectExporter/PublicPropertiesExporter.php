<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

use Brick\VarExporter\Internal\ClassInfo;
use Brick\VarExporter\ObjectExporter;

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
    public function supports($object, ClassInfo $classInfo) : bool
    {
        return ! $classInfo->hasNonPublicProps && ! $classInfo->hasConstructor;
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, ClassInfo $classInfo, int $nestingLevel) : string
    {
        $newObject = 'new ' . '\\' . get_class($object);

        $values = get_object_vars($object);

        if (! $values) {
            return $newObject;
        }

        $result  = $this->varExporter->indent($nestingLevel + 1);
        $result .= '$object = ' . $newObject . ';' . PHP_EOL;

        foreach ($values as $key => $value) {
            $result .= $this->varExporter->indent($nestingLevel + 1);
            $result .= '$object->' . $this->varExporter->escapePropName($key) . ' = ' . $this->varExporter->doExport($value, $nestingLevel + 1) . ';' . PHP_EOL;
        }

        $result .= PHP_EOL;
        $result .= $this->varExporter->indent($nestingLevel + 1);
        $result .= 'return $object;' . PHP_EOL;

        return $this->wrapInClosure($result, $nestingLevel);
    }
}
