<?php

declare(strict_types=1);

namespace Brick\VarExporter;

final class ExportException extends \Exception
{
    /**
     * @param string   $message
     * @param string[] $path
     */
    public function __construct(string $message, array $path)
    {
        if ($path) {
            $message = 'At ' . self::pathToString($path) . ': ' . $message;
        }

        parent::__construct($message);
    }

    /**
     * Returns a string representation of the given path.
     *
     * @param string[] $path
     *
     * @return string
     */
    public static function pathToString(array $path) : string
    {
        return '[' . implode('][', $path) . ']';
    }
}
