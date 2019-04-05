<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

use Brick\VarExporter\Internal\ClassInfo;
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
    public function supports($object, ClassInfo $classInfo) : bool
    {
        return $classInfo->hasSerializeMagicMethods;
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, ClassInfo $classInfo, int $nestingLevel) : string
    {
        if ($classInfo->hasConstructor) {
            $result  = $this->varExporter->indent($nestingLevel + 1);
            $result .= '$class = new \ReflectionClass(\\' . get_class($object) . '::class);' . PHP_EOL;

            $result .= $this->varExporter->indent($nestingLevel + 1);
            $result .= '$object = $class->newInstanceWithoutConstructor();'. PHP_EOL;
        } else {
            $result  = $this->varExporter->indent($nestingLevel + 1);
            $result .= '$object = new \\' . get_class($object) . ';' . PHP_EOL;
        }

        $result .= PHP_EOL;

        $values = $object->__serialize();

        $result .= $this->varExporter->indent($nestingLevel + 1);
        $result .= '$object->__unserialize(' . $this->varExporter->doExport($values, $nestingLevel + 1) . ');' . PHP_EOL;
        $result .= PHP_EOL;

        $result .= $this->varExporter->indent($nestingLevel + 1);
        $result .= 'return $object;' . PHP_EOL;

        return $this->wrapInClosure($result, $nestingLevel);
    }
}
