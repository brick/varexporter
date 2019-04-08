<?php

declare(strict_types=1);

namespace Brick\VarExporter;

use Throwable;

final class ExportException extends \Exception
{
    /**
     * @param string         $message
     * @param string[]       $path
     * @param Throwable|null $previous
     */
    public function __construct(string $message, array $path, ?Throwable $previous = null)
    {
        if ($path) {
            $message = '[' . implode('][', $path) . '] ' . $message;
        }

        parent::__construct($message, 0, $previous);
    }
}
