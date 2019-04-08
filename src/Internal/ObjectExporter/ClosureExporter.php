<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\Internal\ObjectExporter;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

/**
 * Handles closures.
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
    public function export($object, \ReflectionObject $reflectionObject, array $path) : array
    {
        $reflectionFunction = new \ReflectionFunction($object);

        $closureFileName = $reflectionFunction->getFileName();

        if (substr($closureFileName, -19) === ' : eval()\'d code') {
            throw new ExportException('Closure defined in eval()\'d code cannot be exported.');
        }

        $closureStartLine = $reflectionFunction->getStartLine();

        $source = @ file_get_contents($closureFileName);

        if ($source === false) {
            throw new ExportException('Cannot open source file "' . $closureFileName . '" for reading closure code.');
        }

        $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP7);

        try {
            $ast = $parser->parse($source);
        } catch (Error $e) {
            throw new ExportException('Cannot parse file "' . $closureFileName . '" for reading closure code.', 0, $e);
        }

        // Resolve names

        $nameResolver = new NameResolver();
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($nameResolver);

        $ast = $nodeTraverser->traverse($ast);

        // Locate the closure node

        $closuresOnLine = [];

        $enterNode = function(Node $node) use ($closureStartLine, & $closuresOnLine) {
            if ($node instanceof Node\Expr\Closure && $node->getStartLine() === $closureStartLine) {
                $closuresOnLine[] = $node;
            }
        };

        $visitor = new class($enterNode) extends NodeVisitorAbstract {
            /** @var \Closure */
            private $enterNode;

            public function __construct(\Closure $enterNode) {
                $this->enterNode = $enterNode;
            }

            public function enterNode(Node $node) {
                ($this->enterNode)($node);
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $count = count($closuresOnLine);

        if ($count !== 1) {
            throw new ExportException(
                sprintf('Expected exactly 1 closure in %s on line %d, found %d.', $closureFileName, $closureStartLine, $count)
            );
        }

        /** @var Node\Expr\Closure $closure */
        $closure = $closuresOnLine[0];

        // Get the code

        $prettyPrinter = new PrettyPrinter\Standard();
        $code = $prettyPrinter->prettyPrintExpr($closure);

        // Consider the pretty-printer output as a single line, to avoid breaking multiline quoted strings and
        // heredocs / nowdocs. We must leave the indenting responsibility to the pretty-printer.

        return [$code];
    }
}
