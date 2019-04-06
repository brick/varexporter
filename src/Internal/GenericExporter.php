<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal;

use Brick\VarExporter\ExportException;

/**
 * The main exporter implementation, that handles variables of any type.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
final class GenericExporter
{
    /**
     * @var ObjectExporter[]
     */
    private $objectExporters = [];

    /**
     * @var bool
     */
    public $addTypeHints;

    /**
     * @var bool
     */
    public $skipDynamicProperties;

    /**
     * @param bool $addTypeHints
     * @param bool $skipDynamicProperties
     */
    public function __construct(bool $addTypeHints, bool $skipDynamicProperties)
    {
        $this->objectExporters[] = new ObjectExporter\StdClassExporter($this);
        $this->objectExporters[] = new ObjectExporter\InternalClassExporter($this);
        $this->objectExporters[] = new ObjectExporter\SetStateExporter($this);
        $this->objectExporters[] = new ObjectExporter\SerializeExporter($this);
        $this->objectExporters[] = new ObjectExporter\CustomObjectExporter($this);

        $this->addTypeHints          = $addTypeHints;
        $this->skipDynamicProperties = $skipDynamicProperties;
    }

    /**
     * @param mixed $var The variable to export.
     *
     * @return string[] The lines of code.
     *
     * @throws ExportException
     */
    public function export($var) : array
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

        $count = count($array);
        $isNumeric = array_keys($array) === range(0, $count - 1);

        $current = 0;

        foreach ($array as $key => $value) {
            $isLast = (++$current === $count);

            $exported = $this->export($value);

            $prepend = '';
            $append = '';

            if (! $isNumeric) {
                $prepend = var_export($key, true) . ' => ';
            }

            if (! $isLast) {
                $append = ',';
            }

            $exported = $this->wrap($exported, $prepend, $append);
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
    public function exportObject($object) : array
    {
        $reflectionObject = new \ReflectionObject($object);

        foreach ($this->objectExporters as $objectExporter) {
            if ($objectExporter->supports($object, $reflectionObject)) {
                return $objectExporter->export($object, $reflectionObject);
            }
        }

        // This may only happen when $allowReflection is false, as ReflectionExporter accepts any object.

        $className = $reflectionObject->getName();

        throw new ExportException(
            'Class "' . $className . '" cannot be exported without resorting to reflection. ' .
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

    /**
     * @param string[] $lines   The lines of code.
     * @param string   $prepend The string to prepend to the first line.
     * @param string   $append  The string to append to the last line.
     *
     * @return string[]
     */
    public function wrap(array $lines, string $prepend, string $append) : array
    {
        $lines[0] = $prepend . $lines[0];
        $lines[count($lines) - 1] .= $append;

        return $lines;
    }
}
