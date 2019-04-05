<?php

declare(strict_types=1);

namespace Brick\VarExporter\ObjectExporter;

use Brick\VarExporter\Internal\ClassInfo;
use Brick\VarExporter\ObjectExporter;

/**
 * Handles stdClass objects.
 */
class StdClassExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     */
    public function supports($object, ClassInfo $classInfo) : bool
    {
        return $object instanceof \stdClass;
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, ClassInfo $classInfo, int $nestingLevel) : string
    {
        return '(object) ' . $this->varExporter->exportArray((array) $object, $nestingLevel);
    }
}
