<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\ObjectExporter;

use Brick\VarExporter\ExportException;
use Brick\VarExporter\Internal\ObjectExporter;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

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
    public function export($object, \ReflectionObject $reflectionObject, array $path, array $parents) : array
    {
        $reflectionFunction = new \ReflectionFunction($object);

        $file = $reflectionFunction->getFileName();
        $line = $reflectionFunction->getStartLine();

        $ast = $this->parseFile($file, $path);
        $ast = $this->resolveNames($ast);

        $closure = $this->getClosure($ast, $file, $line, $path);

        $prettyPrinter = new ClosureExporter\PrettyPrinter();
        $prettyPrinter->setVarExporterNestingLevel(count($path));

        $code = $prettyPrinter->prettyPrintExpr($closure);

        // Consider the pretty-printer output as a single line, to avoid breaking multiline quoted strings and
        // heredocs / nowdocs. We must leave the indenting responsibility to the pretty-printer.

        return [$code];
    }

    /**
     * Parses the given source file.
     *
     * @param string   $filename The source file name.
     * @param string[] $path     The path to the closure in the array/object graph.
     *
     * @return Node\Stmt[] The AST.
     *
     * @throws ExportException
     */
    private function parseFile(string $filename, array $path) : array
    {
        if (substr($filename, -16) === " : eval()'d code") {
            throw new ExportException("Closure defined in eval()'d code cannot be exported.", $path);
        }

        $source = @ file_get_contents($filename);

        if ($source === false) {
            // @codeCoverageIgnoreStart
            throw new ExportException("Cannot open source file \"$filename\" for reading closure code.", $path);
            // @codeCoverageIgnoreEnd
        }

        $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP7);

        try {
            return $parser->parse($source);
            // @codeCoverageIgnoreStart
        } catch (Error $e) {
            throw new ExportException("Cannot parse file \"$filename\" for reading closure code.", $path, $e);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Resolves namespaced names in the AST.
     *
     * @param Node[] $ast
     *
     * @return Node[]
     */
    private function resolveNames(array $ast) : array
    {
        $nameResolver = new NameResolver();
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($nameResolver);

        return $nodeTraverser->traverse($ast);
    }

    /**
     * Finds a closure in the source file and returns its node.
     *
     * @param array    $ast  The AST.
     * @param string   $file The file name.
     * @param int      $line The line number where the closure is located in the source file.
     * @param string[] $path The path to the closure in the array/object graph.
     *
     * @return Node\Expr\Closure
     *
     * @throws ExportException
     */
    private function getClosure(array $ast, string $file, int $line, array $path) : Node\Expr\Closure
    {
        $finder = new FindingVisitor(function(Node $node) use ($line) : bool {
            return $node instanceof Node\Expr\Closure
                && $node->getStartLine() === $line;
        });

        $traverser = new NodeTraverser();
        $traverser->addVisitor($finder);
        $traverser->traverse($ast);

        $closures = $finder->getFoundNodes();
        $count = count($closures);

        if ($count !== 1) {
            throw new ExportException(sprintf(
                'Expected exactly 1 closure in %s on line %d, found %d.',
                $file,
                $line,
                $count
            ), $path);
        }

        /** @var Node\Expr\Closure $closure */
        $closure = $closures[0];

        if ($closure->uses) {
            throw new ExportException("The closure has bound variables through 'use', this is not supported.", $path);
        }

        return $closure;
    }
}
