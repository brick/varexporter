<?php

declare(strict_types=1);

namespace Brick\VarExporter;

final class VarExporter
{
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
     * @param mixed $var       The variable to export.
     * @param bool  $addReturn Whether to prepend the output with 'return ' and append a semicolon and a newline.
     *                         This makes the code ready to be executed in a PHP file - or eval(), for that matter.
     * @return string
     *
     * @throws ExportException
     */
    public function export($var, bool $addReturn = false) : string
    {
        $lines = $this->doExport($var);
        $export = implode(PHP_EOL, $lines);

        if ($addReturn) {
            return 'return ' . $export . ';' .  PHP_EOL;
        }

        return $export;
    }

    /**
     * @param mixed $var The variable to export.
     *
     * @return string[] The lines of code.
     *
     * @throws ExportException
     */
    public function doExport($var) : array
    {
        switch ($type = gettype($var)) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
                return [var_export($var, true)];

            case 'NULL':
                // lowercase null
                return ['null'];

            case 'array':
                return $this->exportArray($var);

            case 'object':
                return $this->exportObject($var);

            default:
                // resources
                throw new ExportException(sprintf('Type "%s" is not supported.', $type));
        }
    }

    /**
     * @param array $array The array to export.
     *
     * @return string[] The lines of code.
     *
     * @throws ExportException
     */
    public function exportArray(array $array) : array
    {
        if (! $array) {
            return ['[]'];
        }

        $result = [];

        $result[] = '[';

        $isNumeric = array_keys($array) === range(0, count($array) - 1);

        $count = count($array);
        $current = 0;

        foreach ($array as $key => $value) {
            $isLast = (++$current === $count);

            $exported = $this->doExport($value);

            if (! $isNumeric) {
                $exported[0] = var_export($key, true) . ' => ' . $exported[0];
            }

            if (! $isLast) {
                $exported[count($exported) - 1] .= ',';
            }

            $exported = $this->indent($exported);

            $result = array_merge($result, $exported);
        }

        $result[] = ']';

        return $result;
    }

    /**
     * @param object $object The object to export.
     *
     * @return string[] The lines of code.
     *
     * @throws ExportException
     */
    private function exportObject($object) : array
    {
        $reflectionObject = new \ReflectionObject($object);

        foreach ($this->objectExporters as $objectExporter) {
            if ($objectExporter->supports($object, $reflectionObject)) {
                return $objectExporter->export($object, $reflectionObject);
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
     * Indents every non-empty line.
     *
     * @param string[] $lines The lines of code.
     *
     * @return string[] The indented lines of code.
     */
    public function indent(array $lines) : array
    {
        foreach ($lines as & $value) {
            if ($value !== '') {
                $value = '    ' . $value;
            }
        }

        return $lines;
    }
}
