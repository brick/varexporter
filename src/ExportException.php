<?php

declare(strict_types=1);

namespace Brick\VarExporter;

use Throwable;

final class ExportException extends \Exception
{
    /**
     * @param string[] $path
     */
    public function __construct(string $message, array $path, ?Throwable $previous = null)
    {
        if ($path) {
            $message = 'At ' . self::pathToString($path) . ': ' . $message;
        }

        parent::__construct($message, 0, $previous);
    }

    /**
     * Returns a string representation of the given path.
     *
     * @param string[] $path
     */
    public static function pathToString(array $path) : string
    {
        return '[' . implode('][', $path) . ']';
    }
}
