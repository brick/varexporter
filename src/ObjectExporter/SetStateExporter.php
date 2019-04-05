<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

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
        $vars = $classInfo->getObjectVars($object);

        return '\\' . get_class($object) . '::__set_state(' . $this->varExporter->exportArray($vars, $nestingLevel) . ')';
    }
}
