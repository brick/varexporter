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
            $message = '[' . implode('][', $path) . '] ' . $message;
        }

        parent::__construct($message);
    }
}
