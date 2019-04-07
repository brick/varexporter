<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\Reflection\ReflectionTools;
use Brick\VarExporter\ExportException;
use Brick\VarExporter\Internal\Lexer\Lexer;
use Brick\VarExporter\Internal\ObjectExporter;

/**
 * Handles closures.
 *
 * @todo replace class names with FQCN
 * @todo what about namespaced functions?
 * @todo reformat and indent code?
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
class ClosureExporter extends ObjectExporter
{
    /**
     * {@inheritDoc}
     */
    public function supports(\ReflectionObject $reflectionObject) : bool
    {
        return $reflectionObject->getName() === \Closure::class;
    }

    /**
     * {@inheritDoc}
     */
    public function export($object, \ReflectionObject $reflectionObject) : array
    {
        $reflectionFunction = new \ReflectionFunction($object);

        $closureFileName = $reflectionFunction->getFileName();

        if (substr($closureFileName, -19) === ' : eval()\'d code') {
            throw new ExportException('Closure defined in eval()\'d code cannot be exported.');
        }

        $closureStartLine = $reflectionFunction->getStartLine();
        $closureEndLine   = $reflectionFunction->getEndLine();

        $source = @ file_get_contents($closureFileName);

        if ($source === false) {
            throw new ExportException('Cannot open source file "' . $closureFileName . '" for reading closure code.');
        }

        $lexer = new Lexer($source);
        $token = $lexer->getTokenOnLine(T_FUNCTION, $closureStartLine);
        $lexer->moveToToken($token);
        $token = $lexer->moveToNext(T_USE, '{');

        if ($token->type === T_USE) {
            throw new ExportException('Cannot export a closure with variables bound through the use statement.');
        }

        $startIndex = $token->index + 1;

        $level = 1;

        while ($level !== 0) {
            $token = $lexer->moveToNext('{', '}');
            if ($token->type === '{') {
                $level++;
            } else {
                $level--;
            }
        }

        $reflectionTools = new ReflectionTools();
        $parameters = $reflectionTools->exportFunctionParameters($reflectionFunction);

        if ($token->line !== $closureEndLine) {
            throw new ExportException(sprintf('Expected closure ending on line %d, closing bracket found on line %d.', $closureEndLine, $token->line));
        }

        $endIndex = $token->index - 1;

        $tokens = $lexer->getTokenRange($startIndex, $endIndex);

        $code = '';

        foreach ($tokens as $token) {
            $code .= $token->code;
        }

        $lines = [];

        $lines[] = 'function(' . $parameters . ') {';
        $lines[] = $code;
        $lines[] = '}';

        return $lines;
    }
}
