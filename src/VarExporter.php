<?php

declare(strict_types=1);

namespace Brick\VarExporter;

use Brick\VarExporter\Internal\ClassInfo;

final class VarExporter
{
    /**
     * A map of class name to ClassInfo instances.
     *
     * @var ClassInfo[]
     */
    private $classInfo = [];

    /**
     * @var ObjectExporter[]
     */
    private $objectExporters = [];

    /**
     * VarExporter constructor.
     *
     * @param bool $allowReflection Whether to allow classes with a constructor or non-public properties to be exported
     *                              using reflection. Disabled by default. Note that even when this is false, reflection
     *                              may still used to create an empty instance for __unserialize(), but is never used to
     *                              bypass a constructor in another context, or set non-public properties.
     */
    public function __construct(bool $allowReflection = false)
    {
        $this->objectExporters[] = new ObjectExporter\StdClassExporter($this);
        $this->objectExporters[] = new ObjectExporter\InternalClassExporter($this);
        $this->objectExporters[] = new ObjectExporter\SetStateExporter($this);
        $this->objectExporters[] = new ObjectExporter\SerializeExporter($this);
        $this->objectExporters[] = new ObjectExporter\PublicPropertiesExporter($this);

        if ($allowReflection) {
            $this->objectExporters[] = new ObjectExporter\ReflectionExporter($this);
        }
    }

    /**
     * @param mixed $var       A variable to export.
     * @param bool  $addReturn Whether to prepend the output with 'return ' and append a semicolon and a newline.
     *                         This makes the code ready to be executed in a PHP file - or eval(), for that matter.
     * @return string
     *
     * @throws ExportException
     */
    public function export($var, bool $addReturn = false) : string
    {
        $export = $this->doExport($var, 0);

        if ($addReturn) {
            return 'return ' . $export . ';' .  PHP_EOL;
        }

        return $export;
    }

    /**
     * @param mixed $var
     * @param int   $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    public function doExport($var, int $nestingLevel) : string
    {
        switch ($type = gettype($var)) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
                return var_export($var, true);

            case 'NULL':
                // lowercase null
                return 'null';

            case 'array':
                return $this->exportArray($var, $nestingLevel);

            case 'object':
                return $this->exportObject($var, $nestingLevel);

            default:
                // resources
                throw new ExportException(sprintf('Type "%s" is not supported.', $type));
        }
    }

    /**
     * @param array $array
     * @param int   $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    public function exportArray(array $array, int $nestingLevel) : string
    {
        if (! $array) {
            return '[]';
        }

        $result = '[' . PHP_EOL;

        $isNumeric = array_keys($array) === range(0, count($array) - 1);

        $count = count($array);
        $current = 0;

        foreach ($array as $key => $value) {
            $isLast = (++$current === $count);
            $result .= $this->indent($nestingLevel + 1);

            if (! $isNumeric) {
                $result .= var_export($key, true);
                $result .= ' => ';
            }

            $result .= $this->doExport($value, $nestingLevel + 1);

            if (! $isLast) {
                $result .= ',';
            }

            $result .= PHP_EOL;
        }

        $result .= $this->indent($nestingLevel);
        $result .= ']';

        return $result;
    }

    /**
     * @param object $object
     * @param int    $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    private function exportObject($object, int $nestingLevel) : string
    {
        $classInfo = $this->getClassInfo($object);

        foreach ($this->objectExporters as $objectExporter) {
            if ($objectExporter->supports($object, $classInfo)) {
                return $objectExporter->export($object, $classInfo, $nestingLevel);
            }
        }

        // This may only happen when $allowReflection is false, as ReflectionExporter accepts any object.

        throw new ExportException(
            'Class "' . get_class($object) . '" cannot be exported without resorting to reflection. ' .
            'Either implement __set_state() or __serialize() and __unserialize(), ' .
            'or explicitly enable exporting with reflection by passing true to the VarExporter constructor.'
        );
    }

    /**
     * @param int $nestingLevel
     *
     * @return string
     */
    public function indent(int $nestingLevel) : string
    {
        return str_repeat(' ', 4 * $nestingLevel);
    }

    /**
     * Returns a ClassInfo instance for the given object.
     *
     * @param object $object
     *
     * @return ClassInfo
     */
    private function getClassInfo($object) : ClassInfo
    {
        $className = get_class($object);

        if (! isset($this->classInfo[$className])) {
            $this->classInfo[$className] = new ClassInfo($className);
        }

        return $this->classInfo[$className];
    }
}
