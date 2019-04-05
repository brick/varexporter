<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal;

use Brick\VarExporter\ExportException;

/**
 * Holds computed information about a class.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
final class ClassInfo extends \Exception
{
    /**
     * The reflection of the class.
     *
     * @var \ReflectionClass
     */
    public $reflectionClass;

    /**
     * ClassInfo constructor.
     *
     * @param string $className The fully qualified class name.
     *
     * @throws \ReflectionException If the class does not exist.
     */
    public function __construct(string $className)
    {
        $this->reflectionClass = $class = new \ReflectionClass($className);
    }
}
